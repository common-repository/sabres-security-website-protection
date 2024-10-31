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

require_once( __DIR__ . '/../../request.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );
require_once( __DIR__ . '/../../helpers/network.php' );
require_once( __DIR__ . '/../../helpers/server.php' );

class SBRS_Lifecycle_Gateway implements SBRS_Lifecycle_IHandler
{
    /** @var  SBRS_Helper_Server */
    private $server;
    /** @var  SBRS_Request */
    private $request;
    /** @var  SBRS_Settings */
    private $settings;
    /** @var  SBRS_WP */
    private $wp;

    public function __construct($server, $request, $settings, $wp) {
        $this->server = $server;
        $this->request = $request;
        $this->settings = $settings;
        $this->wp = $wp;
    }
    public function write($data) {
        $data = array_merge( $this->collect_request_data(), $data );

        $this->server->call('lifecycle-event', '', $data);
    }


    protected function collect_request_data()
    {
        $unique_id = '';
        if (isset($_COOKIE) && !empty($_COOKIE['sbs_uid'])) {
            $unique_id = trim($_COOKIE['sbs_uid']);
        }

        $data = array(
            'uniqueID' => $unique_id,
            'readAddrCalc' => SBRS_Helper_Network::get_real_ip_address(),
            'websiteServerToken' => $this->settings->websiteSabresServerToken,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'],
        );

        $current_user = $this->wp->wp_get_current_user();

        if ( !$this->wp->is_wp_error( $current_user ) ) {
            $data['username'] = $current_user->user_login;
        }

        return $data;
    }
}
