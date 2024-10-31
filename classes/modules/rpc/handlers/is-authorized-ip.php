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

require_once( __DIR__ . '/../../../settings.php' );

class SBRS_RPC_Is_Authorized_IP {

    /** @var  SBRS_Settings */
    private $settings;

    public function __construct(SBRS_Settings $settings)
    {
        $this->settings = $settings;
    }

    public function execute($rpc_data) {
        $ips = $this->settings->authorized_RPC_IPs;

        if (in_array($_SERVER['REMOTE_ADDR'], $ips)) {
            echo true;
        } else {
            echo false;
        }
    }

}
