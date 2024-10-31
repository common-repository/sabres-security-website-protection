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

require_once( __DIR__ . '/helpers/crypto.php');
require_once( __DIR__ . '/helpers/datetime.php');
require_once( __DIR__ . '/helpers/network.php');

class SBRS_Request
{

    private $is_rpc = false;

    private $request_data = array();

    private $unique_id;

    private $unique_id_hash;

    private $request_unique_id;

    private $request_file_name;

    private $settings;

    public function __construct($settings, $sabres_app_salt)
    {
        $this->settings = $settings;
        $this->request_file_name = SBRS_Helper_Network::get_current_filename();

        if (isset($_COOKIE) && !empty($_COOKIE['sbs_uid'])) {
            $this->unique_id = trim($_COOKIE['sbs_uid']);
        }
        if (empty($this->unique_id)) {
            $this->unique_id = hash_hmac('sha1', time(), $sabres_app_salt);
        }


        if (isset($_COOKIE) && empty($_COOKIE['sbs_huid'])) {
            $this->unique_id_hash = sha1($this->unique_id . $this->settings->websiteSabresServerToken);
        }

        $this->request_unique_id = SBRS_Helper_Crypto::get_random_hash();

        $this->init();
    }


    public function init()
    {
        $this->request_data = array(
            'uniqueid' => $this->unique_id,
            'req_num' => $this->request_unique_id,
            'real_addr_calc' => SBRS_Helper_Network::get_real_ip_address(),
            'ServerTime' => SBRS_Helper_Datetime::get_microtime_string(),
            'websiteSabresServerToken' => $this->settings->websiteSabresServerToken,
            'action' => 'trackServerRequest'
        );
    }

    public function addRequestData($data)
    {
        $this->request_data = array_merge($this->request_data, $data);
    }

    public function resetRequestData($data)
    {
        $this->request_data = $data;
    }

    public function getRequestData()
    {
        return $this->request_data;
    }

    public function isRPC()
    {
        return $this->is_rpc;
    }

    public function setRPCRequest()
    {
        $this->is_rpc = true;
    }

    public function getUniqueID()
    {
        return $this->unique_id;
    }

    public function getRequestID()
    {
        return $this->request_unique_id;
    }

    public function getUniqueIdHash()
    {
        return $this->unique_id_hash;
    }

    public function get_checker_tests()
    {
        return array();
        /*
        return array('check_php_functions' => array(
            'hash_hmac',
            'mcrypt_decrypt',
        ));
        */
    }

}
