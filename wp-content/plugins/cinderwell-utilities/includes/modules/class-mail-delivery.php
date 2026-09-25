<?php
/**
 * Opt-in outbound mail delivery module.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities\Modules;

use Cinderwell_Utilities\Mail_Admin;
use Cinderwell_Utilities\Mail_Log;
use Cinderwell_Utilities\Mail_Manager;
use Cinderwell_Utilities\Mail_Rest_Api;

defined('ABSPATH') || exit;

class Mail_Delivery {
    public function __construct($settings = []) {
        $manager = new Mail_Manager((array) $settings);
        $manager->register();
        new Mail_Rest_Api((array) $settings);
        if (is_admin()) {
            new Mail_Admin((array) $settings);
        }
        add_action(Mail_Log::CLEANUP_HOOK, [Mail_Log::class, 'purge_expired']);
        Mail_Log::sync_schedule(true);
    }
}
