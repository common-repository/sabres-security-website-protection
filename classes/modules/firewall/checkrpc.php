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

require_once( __DIR__ . '/../../helpers/network.php');

final class SBRS_Firewall_Check_RPC
{

    private $ip_address = array(
        array('76.74.255.0', '76.74.255.127'),
        array('76.74.248.128', '76.74.248.255'),
        array('207.198.101.0', '207.198.101.127'),
        array('198.181.116.0', '198.181.119.255'),
        array('192.0.64.0', '192.0.127.255'),
        array('64.34.206.0', '64.34.206.255'),
    );

    private $xml_rpc;

    public function __construct($xml_rpc)
    {
      $this->xml_rpc=$xml_rpc;
    }

    public function check_request()
    {
        $invalid = false;

        if (strcasecmp($this->xml_rpc, 'true') === 0) {            
            $url = SBRS_Helper_Network::get_relative_url();

            if (!empty($url) && strcasecmp($url, '/xmlrpc.php') === 0) {
                $invalid = true;
                $ipLong = ip2long(SBRS_Helper_Network::get_real_ip_address());
                //$ipLong = ip2long( '76.74.255.127' );

                foreach ($this->ip_address as $ipRange) {
                    $ipStart = ip2long($ipRange[0]);
                    $ipEnd = ip2long($ipRange[1]);

                    if ($ipLong >= $ipStart && $ipLong <= $ipEnd) {
                        $invalid = false;
                        break;
                    }
                }
            }
        }

        return $invalid;
    }
}
