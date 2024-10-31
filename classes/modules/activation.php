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

require_once( __DIR__ . '/../logger.php' );
require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../request.php' );
require_once( __DIR__ . '/../settings.php' );
require_once( __DIR__ . '/../wp.php' );
require_once( __DIR__ . '/../helpers/server.php' );




class SBRS_Activation extends SBRS_Module
{

    /** @var  SBRS_Helper_Server */
    private $server;

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_WP */
    private $wp;

    private $plugin_base;
    /** @var  SBRS_Request */
    private $request;
    /** @var  SBRS_Logger */
    private $logger;

    private $init_reached=false;
    private $activate_on_init=false;


    public function __construct($plugin_base, $wp, $settings, $server, $request, $logger)
    {
        $this->plugin_base = $plugin_base;
        $this->wp = $wp;
        $this->settings = $settings;
        $this->server = $server;
        $this->request = $request;
        $this->logger = $logger;
    }


    public function check_server_token()
    {

        $plugin_url = $this->wp->plugins_url($this->plugin_base);
        $update_plugin_url = strcasecmp($this->settings->update_plugin_url, 'true') == 0;

        $response_info = null;
        if ($this->settings->should_trigger_activation()) {
            if (false !== $this->wp->get_transient('sabres_activation_lock'))
              return;
            $this->wp->set_transient('sabres_activation_lock','true',30);
            $parameters = array(
                'hostName'               => SBRS_Helper_Network::remove_protocol($this->wp->get_site_url()),
                'token'                  => $this->settings->token,
                'symmetricEncryptionKey' => $this->settings->symmetricEncryptionKey,
                'verifyHashSalt'         => $this->settings->verifyHashSalt,
                'rpcMethod'              => 'VGET',
                'pluginURL'              => $plugin_url,
                'version'                => SABRES_VERSION,
                'pluginType'             => SABRES_PLUGIN_TYPE,
                'triggerSyncSettings'    => $this->settings->triggerSyncSettings
            );
            $user = $this->wp->wp_get_current_user();
            if ($this->wp->is_user_admin($user)) {
                $parameters['username'] = $user->user_login;
                $parameters['unique_id'] = $this->request->getUniqueID();
                $parameters['realAddrCalc'] = SBRS_Helper_Network::get_real_ip_address();
            }

            $response_info = $this->server->call('activate-plugin-request', '', $parameters);
            $this->settings->reload();
            $this->wp->delete_transient('sabres_activation_lock');
            if (!$this->settings->should_trigger_activation()) {
                $this->logger->log('info', 'check_server_token', 'Plugin successfully activated', $response_info);
            } else {
                $this->logger->log('error', 'check_server_token', 'Plugin activation failed', $response_info);
            }
        } elseif ($update_plugin_url) {
            $response_info = $this->server->call('update-plugin-url', '',
                array(
                    'pluginURL'                => $plugin_url,
                    'websiteSabresServerToken' => $this->settings->websiteSabresServerToken,
                    'token'                    => $this->settings->token
                ));

            $update_url_success = false;
            if ($this->server->is_success($response_info)) {
                $body = json_decode(@$response_info['body'], true);
                if (!empty($body) && !empty($body['updated']) && $body['updated'] === true) {
                    $this->settings->update_plugin_url = 'false';
                    $update_url_success = true;
                    $this->logger->log('info', 'check_server_token', 'Successfully updated plugin url', $response_info);
                }
            }
            if (!$update_url_success) {
                $this->logger->log('error', 'check_server_token', 'Failed updating plugin url', $response_info);
            }
        }

        return $response_info;
    }


    public function init()
    {
      $this->init_reached=true;
      if ($this->activate_on_init)
         $this->check_server_token();
    }


    public function activate()
    {
       if (!$this->init_reached && !$this->activate_on_init)
         $this->settings->trigger_hourly_cron_now=true;
    }


    public function deactivate()
    {

    }


    public function register_hook_callbacks()
    {
        if (!$this->wp->is_plugin_active($this->plugin_base)) //The reason for this is that during wp plugin activation it sabres activation seems to fail
           return; //So we want to delay sabres activation a bit until wordpress activation is finished

        $sabres_hourly_cron_schedule = $this->wp->wp_next_scheduled('sabres_activation_cron');
        if (!$sabres_hourly_cron_schedule || strcasecmp($this->settings->trigger_hourly_cron_now, 'true') == 0) {
            $this->wp->wp_clear_scheduled_hook('sabres_activation_cron');
            $this->wp->wp_schedule_event(time(), 'hourly', 'sabres_activation_cron');
            if ($this->settings->trigger_hourly_cron_now == 'true')
                $this->settings->trigger_hourly_cron_now = 'false';
            $this->activate_on_init=true;
        }
        $this->wp->add_action('sabres_activation_cron', array($this, 'check_server_token'));
        $this->wp->add_action('init', array($this, 'init'));

    }


    public function upgrade($db_version = 0)
    {

    }


    public function get_checker_tests()
    {
        return array();
        //return array('check_php_functions' => array());
    }

}
