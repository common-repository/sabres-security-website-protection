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

require_once( __DIR__ . '/rpc/dispatcher-factory.php' );
require_once( __DIR__ . '/../logger.php' );
require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../request.php' );
require_once( __DIR__ . '/../settings.php' );
require_once( __DIR__ . '/../helpers/cache.php' );
require_once( __DIR__ . '/../helpers/server.php' );

class SBRS_RPC extends SBRS_Module
{
    /** @var SBRS_WP */
    private $wp;

    /** @var SBRS_RPC_Dispatcher_Factory */
    private $factory;

    /** @var  SBRS_Request */
    private $request;

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_Logger */
    private $logger;

    /** @var  SBRS_Helper_Server */
    private $server;

    public function __construct(SBRS_WP $wp,
                                SBRS_RPC_Dispatcher_Factory $factory,
                                SBRS_Request $request,
                                SBRS_Settings $settings,
                                SBRS_Logger $logger,
                                SBRS_Helper_Server $server)
    {
        $this->wp = $wp;
        $this->factory = $factory;
        $this->request = $request;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->server = $server;
        $this->init();
    }


    public function activate()
    {

    }


    public function deactivate()
    {

    }


    public function register_hook_callbacks()
    {
        $this->wp->add_filter('query_vars', array($this, 'add_query_vars'), 0);
        $this->wp->add_action('parse_request', array($this, 'sniff_requests'), 0);
    }


    public function init()
    {
        if (isset($_GET['sabres_api']) && $_GET['sabres_api'] == 'get') {
            $this->request->setRPCRequest();
        }
    }


    public function upgrade($db_version = 0)
    {

    }


    public function add_query_vars($vars)
    {
        $vars[] = 'sabres_api';
        return $vars;
    }

    public function sniff_requests()
    {
        global $wp;

        if (isset($wp->query_vars['sabres_api']) && $wp->query_vars['sabres_api'] === 'get') {
            // Disable time limit
            @ini_set('max_execution_time', '0');
            @set_time_limit(0);

            @ini_set('memory_limit', '-1');

            date_default_timezone_set('UTC');

            SBRS_Helper_Cache::disable_cache();
            $dispatcher = $this->factory->create('dispatcher');
            register_shutdown_function(array($dispatcher, 'onShutDown'));
            $dispatcher->dispatch();
        }
    }

    public function get_checker_tests()
    {
        return array();
        /*
        return array('check_php_functions' => array(
            'apache_get_modules',
            'mcrypt_decrypt',
            'apache_get_modules',
            'stream_get_filters',
            'stream_get_transports',
            'stream_get_wrappers',
            'curl_init',
            'curl_setopt',
            'curl_exec',
            'curl_error',
            'curl_getinfo',
            'curl_close',
            'stream_context_create',
        )); */
    }

    public function update_authorized_IPs()
    {
        if ($this->settings->websiteSabresServerToke=='')
          return;

        $response_info = $this->server->call(
            'plugin-daily-cron',
            '',
            array('websitesabresservertoken' => $this->settings->websiteSabresServerToken)
        );

        $update_url_success = false;

        if ($this->server->is_success($response_info)) {
            $body = json_decode(@$response_info['body'], true);

            if (!empty($body) && !empty($body['authorizedIPs'])) {
                $this->settings->authorized_RPC_IPs = $body['authorizedIPs'];

                $update_url_success = true;
                $this->logger->log(
                    'info',
                    'plugin-daily-cron',
                    'Successfully updated authorized RPC IPs',
                    $response_info);
            }
        }

        if (!$update_url_success) {
            $this->logger->log(
                'error',
                'plugin-daily-cron',
                'Failed updating authorized RPC IPs',
                $response_info
            );
        }
    }
}
