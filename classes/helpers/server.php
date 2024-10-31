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
require_once( __DIR__ . '/../settings.php' );
require_once( __DIR__ . '/../wp.php' );

class SBRS_Helper_Server
{

    /** @var  SBRS_Settings */
    private $settings;
    /** @var  SBRS_WP */
    private $wp;
    /** @var  SBRS_Logger */
    private $logger;

    private $ext_url_provider;

    private $allow_setting_server_offline=false;

    function __construct($ext_url_provider,$wp, $settings, $logger)
    {
        $this->ext_url_provider=$ext_url_provider;
        $this->wp = $wp;
        $this->settings = $settings;
        $this->logger = $logger;
        if (strcasecmp($this->settings->debug, 'true')!==0)
           $this->allow_setting_server_offline=true;
    }

    public function call($code = '', $action = null, $data = null, $request_info_override = null)
    {

        if (!empty($data)) {
            $params = $data;
        } else {
            $params = array();
        }

        if (!empty($action)) {
            $params['action'] = $action;
        }

        $request_info = array(
            'method' => 'POST',
            'timeout' => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'sslverify' => false,
            'blocking' => true,
            'headers' => array('host',$this->get_server_api_hostname()),
            'body' => $params,
            'cookies' => array()
        );
        if ($request_info_override != null)
            $request_info = array_merge($request_info, $request_info_override);

        $url = $this->get_server_api_url() . "/$code";

        $server_offline = false;

        $response_info = $this->wp->wp_remote_post($url, $request_info);

        if ($this->wp->is_wp_error($response_info)) {
            $first_error_message=strtolower($response_info->get_error_message());
            if (preg_match('~could(?:\s*not|n\'t)\s*resolve\s*host~',$first_error_message) && $this->settings->cached_gateway_ip!='') { //host cant be resolved
              $url=str_replace($this->get_server_api_hostname(),$this->settings->cached_gateway_ip,$url); //retry the request using ip address
              $response_info = wp_remote_post( $url, $request_info );
            }
            if ($this->wp->is_wp_error($response_info)) {
              $log_type = 'error';
              $log_message = $response_info->get_error_message();
              if ($this->allow_setting_server_offline && $action==='trackServerRequest')
                $server_offline = true;
            }
        }
        if (!$this->wp->is_wp_error($response_info)) {
          if (!empty($response_info['response']['code'])) {
              $response_code = $response_info['response']['code'];
          }

          if (!empty($response_info['response']['message'])) {
              $response_message = $response_info['response']['message'];
          }
          if ($response_code !== 200 && $code != 'heartbeat') {
              $log_type = 'error';
              $log_message = $response_message;
                if ($this->allow_setting_server_offline && $action==='trackServerRequest')
                  $server_offline = true;
          } else {
              if (!empty($response_info['body'])) {
                  $response_body = @json_decode($response_info['body']);

                  if (isset($response_body->error)) {
                      $log_type = 'error';
                      $log_message = $response_body->error;
                  }
              }
          }
        }


        if ($server_offline != $this->settings->server_offline) {
            //$settings->reset();
            $this->settings->server_offline = $server_offline ? 'true' : '';
        }

        if (!empty($log_type) && !empty($log_message)) {
            switch ($log_type) {
                case 'error':
                    if (is_array($response_info)) {
                        $response_info['error'] = $log_message;
                    }
                    $this->logger->log($log_type, 'server', ($server_offline ? 'Server offline: ' : '')."$log_message: $url", array('query' => $params));
                    break;
                default:
                    $this->logger->log('info', 'server', "$log_message: $url", array('query' => $params));
                    break;
            }
        }

        return $response_info;
    }

    public function get_server_api_hostname() {
      return preg_replace('~(?::|\/).*~','',preg_replace('~https?:\/\/~','',$this->ext_url_provider->get_base_sagw_url_plain()));
    }

    public function get_server_api_url()
    {
        if ($this->settings->https == '' || strcasecmp($this->settings->https, 'true') == 0) {
            return $this->ext_url_provider->get_base_sagw_url();
        } else {
            return $this->ext_url_provider->get_base_sagw_url_plain();
        }
    }

    public function is_success($response_info)
    {
        if ($this->wp->is_wp_error($response_info))
            return false;
        if (!empty($response_info['response']['code'])) {
            $response_code = $response_info['response']['code'];
            if ($response_code == 200)
                return true;
        }
        return false;
    }
}
