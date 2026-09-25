<?php
/**
 * Routes WordPress mail through the configured provider.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined('ABSPATH') || exit;

class Mail_Manager {
    const API_KEY_OPTION = 'cinderwell_utilities_sendgrid_api_key';
    const API_KEY_CONSTANT = 'CINDERWELL_MAIL_SENDGRID_API_KEY';

    private $settings;

    public function __construct(array $settings) {
        $this->settings = $settings;
    }

    public function register() {
        add_filter('pre_wp_mail', [$this, 'preempt_wp_mail'], 10, 2);
    }

    public function preempt_wp_mail($return, $atts) {
        if (null !== $return) {
            return $return;
        }
        $result = $this->send($atts, 'wordpress', false, true);
        return !empty($result['success']);
    }

    public function send_test($recipient, $sandbox = false) {
        $recipient = sanitize_email($recipient);
        if (!$recipient || !is_email($recipient)) {
            return $this->public_failure('invalid_recipient', __('Enter a valid test recipient.', 'cinderwell-utilities'));
        }

        $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $mode = $sandbox ? __('configuration validation', 'cinderwell-utilities') : __('delivery test', 'cinderwell-utilities');
        $atts = [
            'to'          => [$recipient],
            'subject'     => sprintf(__('[%1$s] Cinderwell mail %2$s', 'cinderwell-utilities'), $site_name, $mode),
            'message'     => sprintf(
                __('This is a Cinderwell Mail Delivery test from %1$s (%2$s). No action is required.', 'cinderwell-utilities'),
                $site_name,
                home_url('/')
            ),
            'headers'     => ['Content-Type: text/plain; charset=UTF-8'],
            'attachments' => [],
            'embeds'      => [],
        ];
        return $this->send($atts, $sandbox ? 'validation' : 'test', $sandbox, false);
    }

    public function validate_configuration() {
        if ('' === self::get_api_key()) {
            return new \WP_Error('missing_api_key', __('Add a SendGrid API key before sending mail.', 'cinderwell-utilities'));
        }
        $sender = $this->get_sender();
        if (empty($sender['email']) || !is_email($sender['email'])) {
            return new \WP_Error('missing_sender', __('Add a valid verified sender email before sending mail.', 'cinderwell-utilities'));
        }
        $sending_domain = self::sanitize_sending_domain($this->settings['sending_domain'] ?? '');
        if ('' === $sending_domain) {
            return new \WP_Error('missing_sending_domain', __('Add the domain authenticated in SendGrid before sending mail.', 'cinderwell-utilities'));
        }
        $from_domain = self::get_email_domain($sender['email']);
        $domain_matches = $from_domain === $sending_domain || $this->string_ends_with($from_domain, '.' . $sending_domain);
        if (!$domain_matches) {
            return new \WP_Error(
                'sender_domain_mismatch',
                sprintf(
                    /* translators: 1: sender domain, 2: configured SendGrid domain. */
                    __('The From address uses %1$s, which does not belong to the configured sending domain %2$s.', 'cinderwell-utilities'),
                    $from_domain,
                    $sending_domain
                )
            );
        }
        return true;
    }

    public static function get_api_key() {
        if (defined(self::API_KEY_CONSTANT)) {
            return trim((string) constant(self::API_KEY_CONSTANT));
        }
        return trim((string) get_option(self::API_KEY_OPTION, ''));
    }

    public static function api_key_is_constant() {
        return defined(self::API_KEY_CONSTANT);
    }

    public static function api_key_is_configured() {
        return '' !== self::get_api_key();
    }

    public static function update_api_key($api_key) {
        if (self::api_key_is_constant()) {
            return false;
        }
        $api_key = trim((string) $api_key);
        if ('' === $api_key) {
            return true;
        }
        return update_option(self::API_KEY_OPTION, $api_key, false);
    }

    public static function clear_api_key() {
        if (self::api_key_is_constant()) {
            return false;
        }
        return delete_option(self::API_KEY_OPTION);
    }

    public static function get_sender_defaults() {
        $name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $email = sanitize_email(get_option('admin_email'));
        if (class_exists('Cinderwell\\Addons') && \Cinderwell\Addons::is_enabled('company-details') && class_exists('Cinderwell\\Company_Details')) {
            $company = \Cinderwell\Company_Details::get_settings();
            if (!empty($company['name'])) {
                $name = sanitize_text_field($company['name']);
            }
            if (!empty($company['email']) && is_email($company['email'])) {
                $email = sanitize_email($company['email']);
            }
        }
        return ['name' => $name, 'email' => $email];
    }

    public static function get_email_domain($email) {
        $email = sanitize_email($email);
        $position = strrpos($email, '@');
        return false === $position ? '' : strtolower(substr($email, $position + 1));
    }

    public static function sanitize_sending_domain($domain) {
        $domain = strtolower(trim((string) $domain));
        if (false !== strpos($domain, '@')) {
            $domain = substr($domain, strrpos($domain, '@') + 1);
        }
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = preg_split('#[/:]#', $domain)[0] ?? '';
        $domain = trim($domain, ". \t\n\r\0\x0B");
        if (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $domain)) {
            return '';
        }
        return $domain;
    }

    private function send(array $atts, $source, $sandbox, $fire_core_hooks) {
        $configuration = $this->validate_configuration();
        $normalized = $this->normalize_mail($atts);
        if (is_wp_error($configuration)) {
            $result = $this->public_failure($configuration->get_error_code(), $configuration->get_error_message());
        } elseif (is_wp_error($normalized)) {
            $result = $this->public_failure($normalized->get_error_code(), $normalized->get_error_message());
        } else {
            $provider = $this->get_provider();
            if (is_wp_error($provider)) {
                $result = $this->public_failure($provider->get_error_code(), $provider->get_error_message());
            } else {
                $result = $provider->send($normalized, $sandbox);
            }
        }
        $result = wp_parse_args((array) $result, [
            'success'       => false,
            'status'        => 'failed',
            'response_code' => 0,
            'message_id'    => '',
            'error_code'    => 'provider_error',
            'error_message' => __('The mail provider returned an invalid response.', 'cinderwell-utilities'),
        ]);

        $log_mail = is_wp_error($normalized) ? $this->metadata_from_atts($atts) : $normalized;
        Mail_Log::insert([
            'status'           => $result['status'],
            'provider'         => sanitize_key($this->settings['provider'] ?? 'sendgrid'),
            'source'           => $source,
            'to_addresses'     => $this->plain_addresses($log_mail['to'] ?? []),
            'cc_addresses'     => $this->plain_addresses($log_mail['cc'] ?? []),
            'bcc_addresses'    => $this->plain_addresses($log_mail['bcc'] ?? []),
            'subject'          => $log_mail['subject'] ?? '',
            'response_code'    => $result['response_code'],
            'message_id'       => $result['message_id'],
            'attachment_count' => count((array) ($log_mail['attachments'] ?? [])) + count((array) ($log_mail['embeds'] ?? [])),
            'error_code'       => $result['error_code'],
            'error_message'    => $result['error_message'],
        ]);

        if ($fire_core_hooks) {
            if ($result['success']) {
                do_action('wp_mail_succeeded', $atts);
            } else {
                do_action('wp_mail_failed', new \WP_Error(
                    'cinderwell_mail_failed',
                    $result['error_message'],
                    array_merge($atts, [
                        'provider'      => sanitize_key($this->settings['provider'] ?? 'sendgrid'),
                        'provider_code' => $result['error_code'],
                        'response_code' => $result['response_code'],
                    ])
                ));
            }
        }

        return $result;
    }

    private function get_provider() {
        $providers = apply_filters('cinderwell_utilities_mail_providers', [
            'sendgrid' => Send_Grid_Provider::class,
        ]);
        $key = sanitize_key($this->settings['provider'] ?? 'sendgrid');
        if (empty($providers[$key])) {
            return new \WP_Error('unknown_provider', __('The configured mail provider is unavailable.', 'cinderwell-utilities'));
        }
        $provider = $providers[$key];
        if (is_string($provider) && class_exists($provider)) {
            $provider = new $provider(self::get_api_key());
        } elseif (is_callable($provider)) {
            $provider = call_user_func($provider, self::get_api_key(), $this->settings);
        }
        if (!$provider instanceof Mail_Provider_Interface) {
            return new \WP_Error('invalid_provider', __('The configured mail provider is invalid.', 'cinderwell-utilities'));
        }
        return $provider;
    }

    private function normalize_mail(array $atts) {
        $headers = $this->parse_headers($atts['headers'] ?? []);
        $to = $this->parse_addresses($atts['to'] ?? []);
        if (!$to) {
            return new \WP_Error('missing_recipient', __('The message has no valid recipients.', 'cinderwell-utilities'));
        }

        $from = $this->get_sender();
        $reply_to = $headers['reply_to'];
        if (!$reply_to && !empty($headers['from']['email']) && strtolower($headers['from']['email']) !== strtolower($from['email'])) {
            $reply_to = [$headers['from']];
        }
        $recipient_count = count($to) + count($headers['cc']) + count($headers['bcc']);
        if ($recipient_count > 1000) {
            return new \WP_Error('too_many_recipients', __('SendGrid allows no more than 1,000 recipients per request.', 'cinderwell-utilities'));
        }

        $attachments = $atts['attachments'] ?? [];
        if (!is_array($attachments)) {
            $attachments = array_filter(explode("\n", str_replace("\r\n", "\n", (string) $attachments)));
        }
        $embeds = $atts['embeds'] ?? [];
        if (!is_array($embeds)) {
            $embeds = array_filter(explode("\n", str_replace("\r\n", "\n", (string) $embeds)));
        }

        $content_type = apply_filters('wp_mail_content_type', $headers['content_type'] ?: 'text/plain');
        if (!in_array($content_type, ['text/plain', 'text/html'], true)) {
            $content_type = 'text/plain';
        }

        return [
            'to'           => $to,
            'cc'           => $headers['cc'],
            'bcc'          => $headers['bcc'],
            'from'         => $from,
            'reply_to'     => $reply_to,
            'subject'      => wp_specialchars_decode((string) ($atts['subject'] ?? ''), ENT_QUOTES),
            'message'      => (string) ($atts['message'] ?? ''),
            'content_type' => $content_type,
            'headers'      => $headers['custom'],
            'attachments'  => $attachments,
            'embeds'       => $embeds,
        ];
    }

    private function get_sender() {
        return [
            'email' => sanitize_email($this->settings['from_email'] ?? ''),
            'name'  => sanitize_text_field($this->settings['from_name'] ?? ''),
        ];
    }

    private function parse_headers($headers) {
        $parsed = [
            'from'         => [],
            'cc'           => [],
            'bcc'          => [],
            'reply_to'     => [],
            'content_type' => '',
            'custom'       => [],
        ];
        if (!is_array($headers)) {
            $headers = preg_split('/\r?\n/', (string) $headers);
        }
        foreach ((array) $headers as $key => $line) {
            if (is_string($key)) {
                $name = trim($key);
                $value = trim((string) $line);
            } elseif (false !== strpos((string) $line, ':')) {
                list($name, $value) = array_map('trim', explode(':', (string) $line, 2));
            } else {
                continue;
            }
            $lower = strtolower($name);
            if ('from' === $lower) {
                $addresses = $this->parse_addresses($value);
                $parsed['from'] = $addresses ? $addresses[0] : [];
            } elseif ('cc' === $lower) {
                $parsed['cc'] = array_merge($parsed['cc'], $this->parse_addresses($value));
            } elseif ('bcc' === $lower) {
                $parsed['bcc'] = array_merge($parsed['bcc'], $this->parse_addresses($value));
            } elseif ('reply-to' === $lower) {
                $parsed['reply_to'] = array_merge($parsed['reply_to'], $this->parse_addresses($value));
            } elseif ('content-type' === $lower) {
                $parts = array_map('trim', explode(';', $value));
                $parsed['content_type'] = strtolower($parts[0]);
            } elseif (!in_array($lower, ['mime-version', 'x-mailer', 'content-transfer-encoding', 'to', 'subject', 'received', 'dkim-signature', 'x-sg-id', 'x-sg-eid'], true)) {
                $parsed['custom'][sanitize_text_field($name)] = sanitize_text_field($value);
            }
        }
        return $parsed;
    }

    private function parse_addresses($addresses) {
        $addresses = is_array($addresses) ? $addresses : [$addresses];
        $result = [];
        foreach ($addresses as $address) {
            foreach (str_getcsv((string) $address) as $part) {
                $part = trim($part);
                $name = '';
                $email = $part;
                if (preg_match('/^(.*?)<([^>]+)>$/', $part, $matches)) {
                    $name = trim($matches[1], " \t\n\r\0\x0B\"'");
                    $email = trim($matches[2]);
                }
                $email = sanitize_email($email);
                if ($email && is_email($email)) {
                    $item = ['email' => $email];
                    if ('' !== $name) {
                        $item['name'] = sanitize_text_field($name);
                    }
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

    private function metadata_from_atts(array $atts) {
        $headers = $this->parse_headers($atts['headers'] ?? []);
        return [
            'to'          => $this->parse_addresses($atts['to'] ?? []),
            'cc'          => $headers['cc'],
            'bcc'         => $headers['bcc'],
            'subject'     => sanitize_text_field($atts['subject'] ?? ''),
            'attachments' => (array) ($atts['attachments'] ?? []),
            'embeds'      => (array) ($atts['embeds'] ?? []),
        ];
    }

    private function plain_addresses(array $addresses) {
        return array_values(array_filter(array_map(static function ($address) {
            return is_array($address) ? sanitize_email($address['email'] ?? '') : sanitize_email($address);
        }, $addresses)));
    }

    private function public_failure($code, $message) {
        return [
            'success'       => false,
            'status'        => 'failed',
            'response_code' => 0,
            'message_id'    => '',
            'error_code'    => sanitize_key($code),
            'error_message' => sanitize_textarea_field($message),
        ];
    }

    private function string_ends_with($haystack, $needle) {
        if ('' === $needle) {
            return true;
        }
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
