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

require_once( __DIR__ . '/logger.php');
require_once( __DIR__ . '/request.php');
require_once( __DIR__ . '/settings.php');
require_once( __DIR__ . '/wp.php');
require_once( __DIR__ . '/helpers/network.php');
require_once( __DIR__ . '/helpers/server.php');
require_once( __DIR__ . '/helpers/utils.php');

class SBRS_Traffic_Dispatcher
{

    /** @var  SBRS_Request */
    private $request;

    /** @var  SBRS_Helper_Server */
    private $server;

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_WP */
    private $wp;

    /** @var  SBRS_Logger */
    private $logger;

    private $request_dispatched = false;

    private $ext_url_provider;

    public function __construct($ext_url_provider,$request, $server, $settings, $wp, $logger)
    {
        $this->ext_url_provider = $ext_url_provider;
        $this->request = $request;
        $this->server = $server;
        $this->settings = $settings;
        $this->wp = $wp;
        $this->logger = $logger;
    }

    public function dispatch_request($custom = null)
    {
        if (!$this->request_dispatched) {

            $request_data = $this->request->getRequestData();

            $prefixes = array(
                'request' => 'req_',
                'response' => 'res_',
                'server' => 'srv_'
            );
            $requestHeadersNames = array('Authorization', 'Cookie', 'Request_Method', 'Content-Length', 'Forwarded', 'From', 'Host', 'Origin', 'Proxy-Authorization', 'Referer', 'User-Agent', 'Via', 'X-Requested-With', 'DNT', 'X-Forwarded-For', 'X-Forwarded-Host', 'X-Forwarded-Proto', 'Front-End-Https', 'X-Http-Method-Override', 'X-Csrf-Token', 'X-CSRFToken', 'X-XSRF-TOKEN');
            $responseHeadersNames = array('Access-Control-Allow-Origin', 'Cache-Control', 'Link', 'Location', 'Pragma', 'Refresh');
            $serverVariablesNames = array('Server_Addr', 'Server_Name', 'Request_Method', 'Https', 'Remote_Addr', 'Remote_Host', 'Remote_User', 'Redirect_Remote_User', 'Server_Admin', 'Request_Uri', 'Php_Auth_User', 'Php_Auth_Pw', 'Auth_Type');
            $excludedServerVariablesNames = array('argv', 'argc', 'Server_Software', 'Server_Admin', 'Path', 'SystemRoot', 'Comspec', 'pathext', 'Document_Root', 'Server_Signature', 'Script_Filename');

            $requestHeaders = array();
            $responseHeaders = array();
            $serverVariables = array();

            foreach ($_SERVER as $key => $value) {
                if (strpos(strtolower($key), 'http_') === 0) {
                    $newKey = str_replace('_', '-', substr($key, 5));
                    $requestHeaders[trim($newKey)] = !is_array($value) ? trim($value) : $value;
                } else {
                    $serverVariables[trim($key)] = !is_array($value) ? trim($value) : $value;
                }
            }
            foreach (headers_list() as $header) {
                list($key, $value) = explode(':', $header, 2);

                $responseHeaders[trim($key)] = trim($value);
            }
            $request_data = array_merge($request_data,
                SBRS_Helper_Utils::array_refactor_keys(SBRS_Helper_Utils::array_intersect_assoc_key($requestHeaders, $requestHeadersNames), $prefixes['request']),
                SBRS_Helper_Utils::array_refactor_keys(SBRS_Helper_Utils::array_intersect_assoc_key($responseHeaders, $responseHeadersNames), $prefixes['response']),
                SBRS_Helper_Utils::array_refactor_keys(SBRS_Helper_Utils::array_diff_assoc_key(SBRS_Helper_Utils::array_intersect_assoc_key($serverVariables, $serverVariablesNames), $excludedServerVariablesNames), $prefixes['server'])
            );

            $payload = array_merge(
                SBRS_Helper_Utils::array_refactor_keys(SBRS_Helper_Utils::array_diff_assoc_key($requestHeaders, $requestHeadersNames), $prefixes['request']),
                SBRS_Helper_Utils::array_refactor_keys(SBRS_Helper_Utils::array_diff_assoc_key($responseHeaders, $responseHeadersNames), $prefixes['response']),
                SBRS_Helper_Utils::array_refactor_keys(SBRS_Helper_Utils::array_diff_assoc_key($serverVariables, array_merge($serverVariablesNames, $excludedServerVariablesNames)), $prefixes['server'])
            );

            ksort($request_data);
            $request_data = array_change_key_case($request_data, CASE_LOWER);

            ksort($payload);
            $request_data['payload'] = array_change_key_case($payload, CASE_LOWER);


            $request_data['payload'] = json_encode($request_data['payload']);
            $request_data = array_change_key_case($request_data, CASE_LOWER);

            if (!empty($custom)) {
                $request_data = array_merge($request_data, $custom);
            }

            $this->server->call('server-agent-gateway', null, $request_data);
            //No need to log every request
            //$this->logger->log('info', 'dispatcher_request', null, $request_data);

            $this->request_dispatched = true;
        }
    }

    public function send_cookies()
    {
        if (empty($_COOKIE['sbs_uid'])) {
            $expires = (60 * 60 * 24) * (30 * 12) * 4 + time(); // 4 Years
            setcookie('sbs_uid', $this->request->getUniqueID(), $expires, '/', COOKIE_DOMAIN, false, true);
        }

        if (empty($_COOKIE['sbs_huid'])) {
            $expires = (60 * 60 * 24) * (30 * 12) * 4 + time(); // 4 Years
            setcookie('sbs_huid', $this->request->getUniqueIdHash(), $expires, '/', COOKIE_DOMAIN, false, false);
        }
        // Cache
        $url = SBRS_Helper_Network::get_actual_url();

        if (!empty($url) && count($_COOKIE) < 50) {
          // Serialized "Value" + "Domain" + "Expires / Max-Age" (~24 bytes) + "Size" (~3 bytes) + "HTTP" (~1 byte) + 6 dividers (;)
            $raw_cookie_length = strlen(serialize($_COOKIE)) + (strlen(COOKIE_DOMAIN) + count($_COOKIE)) * 34;

            // Browser cookie limits for common browsers: do not exceed 50 cookies per domain,
            // and 4093 bytes per domain (ie the size of all cookies should not exceed 4093 bytes).
            // http://browsercookielimits.squawky.net/

            $cookie_prefix = SBRS_Helper_Utils::is_ajax() ? 'sbs_ajax_r_' : 'sbs_r_';
            $cookie_name=$cookie_prefix . $this->request->getRequestID();
            $cookie_value = $this->request->getUniqueID() . ':' . strtolower(SBRS_Helper_Utils::to_hex($url)) . ':' . round(microtime(true) * 1000);
            if ($raw_cookie_length + strlen($cookie_name) + strlen($cookie_value) + strlen(COOKIE_DOMAIN) + 35 < 4093) {
                setcookie($cookie_name, $cookie_value, 0, '/', COOKIE_DOMAIN, false, false);
            }            
        }
    }

    public function write_client_script($firewallAction)
    {
        $plugin_url = $this->wp->plugins_url($this->wp->plugin_basename(SABRES_PATH));
        $plugin_url = preg_replace('#^https?:#', '', $plugin_url);

        $unique_id = $this->request->getUniqueID();
        $request_unique_id = $this->request->getRequestID();
        $client_token = $this->settings->websiteSabresClientToken;
        $api_url = $this->get_client_api_url() . '/client-agent-gateway';

        echo <<<EOL
<script type="text/javascript">
    "use strict";

    if (typeof initial_sbs_data === 'undefined') {
      var initial_sbs_data= {
          uniqueID:"$unique_id",
          requestID:"$request_unique_id",
          clientToken:"$client_token",
          apiURL:"$api_url",
          pluginURL:"$plugin_url"
       };
      if ( '$firewallAction' !== '' ) {
        initial_sbs_data.firewallAction='$firewallAction';
      }
      (function() {
          "use strict";

          var s = document.createElement('script');
          s.type = 'text/javascript';
          s.async = true;
          s.src = initial_sbs_data.pluginURL + '/javascript/sabres.js?r=' + Math.random();

          var l = document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] || document.getElementsByTagName('html')[0];

          if (l) l.appendChild(s);
      })();
    }
</script>
EOL;
    }

    private function get_client_api_url()
    {
        if ($this->settings->https == '' || strcasecmp($this->settings->https, 'true') == 0) {
            return $this->ext_url_provider->get_base_cagw_url();
        } else {
            return $this->ext_url_provider->get_base_cagw_url_plain();
        }
    }
}
