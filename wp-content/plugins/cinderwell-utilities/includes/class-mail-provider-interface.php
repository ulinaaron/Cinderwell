<?php
/**
 * Contract for outbound mail providers.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined('ABSPATH') || exit;

interface Mail_Provider_Interface {
    /**
     * Send normalized mail data.
     *
     * @param array $mail    Normalized mail data.
     * @param bool  $sandbox Validate without delivering.
     * @return array{success:bool,status:string,response_code:int,message_id:string,error_code:string,error_message:string}
     */
    public function send(array $mail, $sandbox = false);
}
