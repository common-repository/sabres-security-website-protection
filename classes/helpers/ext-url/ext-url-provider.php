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

class SBRS_External_URL_Provider {
    
    public function __construct() {

    }
    // TODO Change to https
    public function get_admin_api_url() {
        return 'https://ca-gateway.sabressecurity.com/wp-admin';
    }

    public function get_admin_api_url_plain() {
        return 'http://ca-gateway.sabressecurity.com/wp-admin';
    }

    public function get_base_cagw_url() {
        return 'https://ca-gateway.sabressecurity.com';
    }

    public function get_base_cagw_url_plain() {
      return 'http://ca-gateway.sabressecurity.com';
    }

    public function get_base_sagw_url() {
        return 'https://sa-gateway.sabressecurity.com';
    }

    public function get_base_sagw_url_plain() {
       return 'http://sa-gateway.sabressecurity.com';
    }

    public function get_portal_api_url() {
        return 'https://portal.sabressecurity.com/api/plugin';
    }

    public function get_portal_api_url_plain() {
        //this is https as well only accessed from client side
        return 'https://portal.sabressecurity.com/api/plugin';
    }

    public function get_base_portal_url() {
        return 'https://portal.sabressecurity.com';
    }

    public function get_base_portal_url_plain() {
      //this is https as well only accessed from client side
        return 'https://portal.sabressecurity.com';
    }

    public function get_plugin_download_url() {
      return 'http://wp-plugin.sabressecurity.com/store/download';
    }
}
