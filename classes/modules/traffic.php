<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Copyright 2016 Sabres Security Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../request.php' );
require_once( __DIR__ . '/../settings.php' );
require_once( __DIR__ . '/../traffic-dispatcher.php' );
require_once( __DIR__ . '/../wp.php' );

class SBRS_Traffic extends SBRS_Module
{

    private $response_ob_content;

    /** @var SBRS_WP */
    private $wp;

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_Request */
    private $request;

    /** @var  SBRS_Traffic_Dispatcher */
    private $traffic_dispatcher;

    private $logger;

    private $response_code=200;

    function __construct(SBRS_WP $wp, $settings, $request, $dispatcher,$logger)
    {
        $this->wp = $wp;
        $this->settings = $settings;
        $this->request = $request;
        $this->traffic_dispatcher = $dispatcher;
        $this->logger=$logger;
    }

    public function dispatch_request()
    {
        $custom = array();
        if ( !empty( $this->response_code ) )
            $custom['response_code'] = $this->response_code;
        $this->traffic_dispatcher->dispatch_request($custom);
    }


    public function activate()
    {

    }


    public function deactivate()
    {

    }

    public function upgrade($db_version = 0)
    {

    }

    public function register_hook_callbacks()
    {
        if (!$this->request->isRPC()) {
            $this->wp->add_action('init', array($this, 'init'), 10);
            $this->wp->add_action('wp_footer', array($this, 'hook_wp_footer'));
            if ($this->wp->is_admin()) {
                $this->wp->add_action('admin_footer', array($this, 'hook_wp_footer'));
            }
            $this->wp->add_action('login_footer', array($this, 'hook_wp_footer'));
            $this->wp->add_action('shutdown', array($this, 'dispatch_request'));
            $this->wp->add_filter( 'status_header', array( $this, 'hook_status_header' ) );
        }

    }

    public function init()
    {
        $this->traffic_dispatcher->send_cookies();
    }

    public function hook_wp_footer()
    {
        $this->traffic_dispatcher->write_client_script('');
    }

    public function is_enabled()
    {



        // Active
        if (!strcasecmp($this->settings->isActive, 'true') == 0) return false;

        // Server offline
        if (strcasecmp($this->settings->server_offline, 'true') == 0) return false;

        // Check tokens
        if ($this->settings->websiteSabresServerToken === '' || $this->settings->websiteSabresClientToken === '') return false;

        return true;
    }

    public function hook_status_header( $status_header )
    {
        try {
            if ( !empty( $status_header ) )
                list( $protocol, $response_code ) = explode( ' ', $status_header );

            if ( !empty( $response_code ) )
                $this->response_code = $response_code;
        } catch ( \Exception $e ) {
            $error_message = $e->getMessage();

            $this->logger->log( 'error', 'Hook - status_header', $error_message ,$e,false);
        }
    }
}
