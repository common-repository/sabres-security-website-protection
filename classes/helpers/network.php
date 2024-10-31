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

require_once( __DIR__ . '/error.php');

if (!class_exists('SBRS_Helper_Network')) {

    /**
     * The Sabres Net Class
     *
     * @author Sabres Security inc
     * @package Sabres_Security_Plugin
     * @since 1.0.0
     */
    abstract class SBRS_Helper_Network
    {
        public static $ssl_constants = array(
            'FORCE_SSL_LOGIN',
            'FORCE_SSL_ADMIN'
        );

        public static function download_file($url, $file_path, $content_type = null)
        {
            if (empty($url)) SBRS_Helper_Error::throwError('Url cannot remain empty');
            if (empty($file_path)) SBRS_Helper_Error::throwError('File path cannot remain empty');

//            if ( !self::is_exists( $url, $content_type ) )
//                SBRS_Helper_Error::throwError( sprintf( 'File not found: %s', $url ) );

            $file_dir = dirname($file_path);

            if (!is_dir($file_dir))
                SBRS_Helper_Error::throwError(sprintf('Directory not found: %s', $file_dir));

            if (!is_writable($file_dir))
                @chmod($file_dir, 0755);

            if (!is_writable(dirname($file_dir)))
                SBRS_Helper_Error::throwError(sprintf('Can\'t write file: %s Check your permissions!', $file_dir));

            if (@$in_file = fopen($url, 'rb')) {
                if (@$out_file = fopen($file_path, 'wb')) {

                    while ($chunk = fread($in_file, 1024 * 8)) {
                        fwrite($out_file, $chunk, 1024 * 8);
                    }

                    fclose($out_file);

                    return true;
                } else {

                    fclose($in_file);

                    SBRS_Helper_Error::throwError(sprintf('Error downloading file: %s', $file_path));
                }

                fclose($in_file);
            } else {
                SBRS_Helper_Error::throwError(sprintf('Error connecting to file: %s', $url));
            }

            return false;
        }

        public static function is_exists($url, $content_type = null)
        {
            if (empty($url)) return false;

            if (function_exists('get_headers')) {
                $headers = @get_headers($url, 1);

                if (count($headers) > 0) {
                    $status = $headers[0];

                    if ($status != '') {
                        $status_bits = explode(' ', $status);

                        if (count($status_bits) > 1) {
                            $status_code = $status_bits[1];

                            // Check status code
                            if (!($status_code >= 400 && $status_code < 500)) {
                                // Check content type
                                if ($content_type != '' && isset($headers['Content-Type'])) {
                                    $content_type_header = $headers['Content-Type'];

                                    if ($content_type != '' && $content_type_header != '') {
                                        if (strpos('content_type_header', ';') !== false) {
                                            $content_type_header = strstr($content_type_header, ';', true);
                                        }

                                        if (strcasecmp($content_type_header, $content_type) == 0) {
                                            return true;
                                        }
                                    }
                                } else {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }

            return false;
        }

        public static function get_real_ip_address()
        {
            $server_attributes = array('HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            );

            foreach ($server_attributes as $attr)
                if (!empty($_SERVER[$attr]))
                    return $_SERVER[$attr];
        }

        public static function get_all_ip_addresses()
        {
            $result = array();
            $server_attributes = array('HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            );
            foreach ($server_attributes as $attr)
                if (!empty($_SERVER[$attr])) {
                    $ip = SBRS_Helper_Network::ip2long(trim($_SERVER[$attr]));
                    if ($ip)
                        array_push($result, $ip);
                }
            return $result;
        }

        public static function get_request_ips()
        {
            $result = array();
            $server_attributes = array('HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            );
            foreach ($server_attributes as $attr) {
                if (!empty($_SERVER[$attr])) {
                    $result[$attr] = trim($_SERVER[$attr]);
                }
            }
            return $result;

        }

        public static function get_actual_url()
        {
            return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        public static function disable_cache()
        {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        }

        public static function remove_protocol($url)
        {
            return preg_replace('#^https?://#', '', $url);
        }

        public static function get_query_string()
        {
            return $_SERVER["QUERY_STRING"];
        }

        public static function ip2long($ip)
        {
            $long = ip2long($ip);

            if ($long === false) {
                return 1;

            } else {
                return $long;
            }
        }

        public static function base64url_encode($data)
        {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        }

        public static function base64url_decode($data)
        {
            return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
        }

        public static function get_current_filename()
        {
            return basename($_SERVER['PHP_SELF']);
        }

        public static function get_relative_url()
        {
            if (!empty($_SERVER['PHP_SELF'])) {
                return $_SERVER['PHP_SELF'];
            }
        }

        public static function parse_url($url)
        {
            $ret = array();
            $urlParts = explode('&', $url);

            foreach ($urlParts as $part) {
                $segments = explode('=', $part, 2);

                if (count($segments) > 1) {
                    $ret[$segments[0]] = $segments[1];
                }
            }

            return $ret;
        }

        public static function is_wp_login_action($action_list)
        {
            $uri = $_SERVER['REQUEST_URI'];
            if (!empty($uri) && strpos($uri, '/wp-login.php') === 0) {
                $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
                return in_array($action, $action_list);
            }
            return false;
        }

        public static function get_request_path($uri=null) {
            if (empty($uri))
                $uri=$_SERVER['REQUEST_URI'];
            $qs_index=strrpos($uri,'?');
            if ($qs_index!==false)
                $uri=substr($uri,0,$qs_index);
            return $uri;
        }
    }
}
