<?php
/**
 * SendGrid v3 Mail Send provider.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined('ABSPATH') || exit;

class Send_Grid_Provider implements Mail_Provider_Interface {
    const ENDPOINT = 'https://api.sendgrid.com/v3/mail/send';
    const MAX_PAYLOAD_BYTES = 30000000;

    private $api_key;

    public function __construct($api_key) {
        $this->api_key = (string) $api_key;
    }

    public function send(array $mail, $sandbox = false) {
        if ('' === $this->api_key) {
            return $this->failure('missing_api_key', __('SendGrid API key is not configured.', 'cinderwell-utilities'));
        }

        $payload = $this->build_payload($mail, $sandbox);
        if (is_wp_error($payload)) {
            return $this->failure($payload->get_error_code(), $payload->get_error_message());
        }

        $json = wp_json_encode($payload);
        if (false === $json || strlen($json) >= self::MAX_PAYLOAD_BYTES) {
            return $this->failure('payload_too_large', __('The message exceeds SendGrid’s 30 MB request limit.', 'cinderwell-utilities'));
        }

        $response = wp_remote_post(self::ENDPOINT, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => $json,
        ]);

        if (is_wp_error($response)) {
            return $this->failure('network_error', $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $expected = $sandbox ? 200 : 202;
        $message_id = sanitize_text_field((string) wp_remote_retrieve_header($response, 'x-message-id'));
        if ($expected === $code) {
            return [
                'success'       => true,
                'status'        => $sandbox ? 'validated' : 'accepted',
                'response_code' => $code,
                'message_id'    => $message_id,
                'error_code'    => '',
                'error_message' => '',
            ];
        }

        $body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        $messages = [];
        if (is_array($decoded) && !empty($decoded['errors']) && is_array($decoded['errors'])) {
            foreach ($decoded['errors'] as $error) {
                if (!empty($error['message'])) {
                    $messages[] = sanitize_text_field($error['message']);
                }
            }
        }
        $message = $messages ? implode(' ', $messages) : wp_strip_all_tags(substr($body, 0, 1000));
        if ('' === $message) {
            $message = sprintf(__('SendGrid returned HTTP %d.', 'cinderwell-utilities'), $code);
        }

        return $this->failure('sendgrid_rejected', $message, $code, $message_id);
    }

    private function build_payload(array $mail, $sandbox) {
        $personalization = ['to' => $mail['to']];
        if ($mail['cc']) {
            $personalization['cc'] = $mail['cc'];
        }
        if ($mail['bcc']) {
            $personalization['bcc'] = $mail['bcc'];
        }

        $payload = [
            'personalizations' => [$personalization],
            'from'             => $mail['from'],
            'subject'          => $mail['subject'],
            'content'          => [[
                'type'  => $mail['content_type'],
                'value' => $mail['message'],
            ]],
        ];
        if (!empty($mail['reply_to'])) {
            $payload['reply_to'] = $mail['reply_to'][0];
        }
        if (!empty($mail['headers'])) {
            $payload['headers'] = $mail['headers'];
        }

        $attachments = [];
        foreach ((array) $mail['attachments'] as $name => $path) {
            $attachment = $this->file_payload($path, is_string($name) ? $name : '', 'attachment');
            if (is_wp_error($attachment)) {
                return $attachment;
            }
            $attachments[] = $attachment;
        }
        foreach ((array) $mail['embeds'] as $cid => $path) {
            $embed_args = apply_filters('wp_mail_embed_args', [
                'path'        => $path,
                'cid'         => (string) $cid,
                'name'        => basename($path),
                'encoding'    => 'base64',
                'type'        => '',
                'disposition' => 'inline',
            ]);
            $attachment = $this->file_payload($embed_args['path'], $embed_args['name'], 'inline', $embed_args['cid'], $embed_args['type']);
            if (is_wp_error($attachment)) {
                return $attachment;
            }
            $attachments[] = $attachment;
        }
        if ($attachments) {
            $payload['attachments'] = $attachments;
        }
        if ($sandbox) {
            $payload['mail_settings'] = ['sandbox_mode' => ['enable' => true]];
        }

        return $payload;
    }

    private function file_payload($path, $name = '', $disposition = 'attachment', $content_id = '', $type = '') {
        $path = (string) $path;
        if ('' === $path || !is_file($path) || !is_readable($path)) {
            return new \WP_Error('attachment_unreadable', sprintf(__('Attachment is not readable: %s', 'cinderwell-utilities'), basename($path)));
        }
        $contents = file_get_contents($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local attachment path supplied to wp_mail().
        if (false === $contents) {
            return new \WP_Error('attachment_unreadable', sprintf(__('Attachment could not be read: %s', 'cinderwell-utilities'), basename($path)));
        }
        if ('' === $type) {
            $checked = wp_check_filetype($path);
            $type = !empty($checked['type']) ? $checked['type'] : 'application/octet-stream';
        }

        $payload = [
            'content'     => base64_encode($contents),
            'type'        => sanitize_mime_type($type),
            'filename'    => sanitize_file_name($name ?: basename($path)),
            'disposition' => 'inline' === $disposition ? 'inline' : 'attachment',
        ];
        if ('inline' === $payload['disposition'] && '' !== $content_id) {
            $payload['content_id'] = sanitize_text_field($content_id);
        }
        return $payload;
    }

    private function failure($code, $message, $response_code = 0, $message_id = '') {
        return [
            'success'       => false,
            'status'        => 'failed',
            'response_code' => (int) $response_code,
            'message_id'    => (string) $message_id,
            'error_code'    => sanitize_key($code),
            'error_message' => sanitize_textarea_field($message),
        ];
    }
}
