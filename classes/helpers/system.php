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

require_once( __DIR__ . '/network.php' );

abstract class SBRS_Helper_System
{

    public static function is_cgi()
    {
        $sapi_type = php_sapi_name();

        $res = substr($sapi_type, 0, 3) == 'cgi';

        return $res;
    }

    public static function is_ssl()
    {
        $res = true;

        foreach (SBRS_Helper_Network::$ssl_constants as $key) {
            if (!defined($key) || !constant($key)) {
                $res = false;
            }
        }

        return $res;
    }

    public static function get_info()
    {
        global $wp_version;

        $info = array();

        $info['uname'] = php_uname();
        $info ['server_api'] = php_sapi_name();
        if (!empty($_SERVER['SERVER_SOFTWARE'])) {
            $info['server_software'] = $_SERVER['SERVER_SOFTWARE'];
        }
        if (function_exists('apache_get_modules')) {
            $info['modules'] = apache_get_modules();
        }

        $info['filters'] = stream_get_filters();
        $info['transports'] = stream_get_transports();
        $info['wrappers'] = stream_get_wrappers();

        $info['php_version'] = phpversion();
        $info['php_ini'] = ini_get_all();

        $info['wp_version'] = $wp_version;

        $info['is_ssl'] = self::is_ssl();

        $info['sbs_version'] = SBS_VERSION;
        $info['sbs_db_version'] = SBS_DB_VERSION;

        return $info;
    }

    public static function get_ssl_info()
    {
        $info = array();

        $info['is_ssl'] = self::is_ssl();

        return $info;
    }

}
