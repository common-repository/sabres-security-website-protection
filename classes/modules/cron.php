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

require_once( 'rpc.php' );
require_once( __DIR__ . '/../logger.php' );
require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../settings.php' );
require_once( __DIR__ . '/../wp.php' );
require_once( __DIR__ . '/../helpers/server.php' );

final class SBRS_Cron extends SBRS_Module
{

    /** @var SBRS_Settings */
    private $settings;
    /** @var SBRS_Helper_Server */
    private $server;
    /** @var SBRS_Logger */
    private $logger;
    /** @var SBRS_WP */
    private $wp;
    /** @var SBRS_RPC */
    private $rpc;

    public function __construct(SBRS_WP $wp,
                                SBRS_Settings $settings,
                                SBRS_Helper_Server $server,
                                SBRS_Logger $logger,
                                SBRS_RPC $rpc)
    {
        $this->wp = $wp;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->server = $server;
        $this->rpc = $rpc;
    }

    public function init()
    {
        $sabres_hourly_cron_schedule = $this->wp->wp_next_scheduled('sabres_hourly_cron');
        if (!$sabres_hourly_cron_schedule || $this->settings->trigger_hourly_cron_now == 'true') {
            $this->wp->wp_clear_scheduled_hook('sabres_hourly_cron');
            $this->wp->wp_schedule_event(time(), 'hourly', 'sabres_hourly_cron');
            if ($this->settings->trigger_hourly_cron_now == 'true')
                $this->settings->trigger_hourly_cron_now = 'false';
            self::hourly_cron();
        }
        $this->wp->add_action('sabres_hourly_cron', array($this, 'hourly_cron'));

        $sabres_daily_cron_schedule = $this->wp->wp_next_scheduled('sabres_daily_cron');
        if (!$sabres_daily_cron_schedule) {
            $this->wp->wp_clear_scheduled_hook('sabres_daily_cron');
            $this->wp->wp_schedule_event(time(), 'daily', 'sabres_daily_cron');
        }
        $this->wp->add_action('sabres_daily_cron', array($this, 'daily_cron'));
    }

    public function activate()
    {

    }


    public function deactivate()
    {
        $this->delete_cron_jobs();
    }

    public function register_hook_callbacks()
    {
        $this->wp->add_action('init', array($this, 'init'), 0);
    }

    public function upgrade($db_version = 0)
    {

    }

    public function delete_cron_jobs()
    {
        $this->wp->wp_clear_scheduled_hook('sabres_daily_cron');
        $schedule = $this->wp->wp_get_schedule('sabres_daily_cron');

        $this->wp->wp_clear_scheduled_hook('sabres_hourly_cron');
        $schedule .= $this->wp->wp_get_schedule('sabres_hourly_cron');

        $this->wp->wp_clear_scheduled_hook('sabres_activation_cron');
        $schedule .= $this->wp->wp_get_schedule('sabres_activation_cron');


        return $schedule;

    }

    public function hourly_cron()
    {
        $server_offline = strcasecmp($this->settings->server_offline, 'true') == 0;
        if ($server_offline) {
            $this->server->call('heartbeat');
        }
        if (!$server_offline && function_exists('gethostbyname')) {
          $gateway_ip=gethostbyname($this->server->get_server_api_hostname());
          if ($gateway_ip!=$this->server->get_server_api_hostname()) {
             $this->settings->cached_gateway_ip=$gateway_ip;
          }
        }
    }

    public function daily_cron()
    {
        $timestamp = $this->wp->get_current_time();

        if ($timestamp) {
            $time = strtotime('-28 days', $timestamp);
            $time = date('Y-m-d H:i:s', $time);

            $this->logger->clear_entries(null, $time);
        }

        $this->rpc->update_authorized_IPs();

    }
}
