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

require_once( __DIR__ . '/../../wp.php' );
require_once( __DIR__ . '/../../helpers/fail.php' );
require_once( __DIR__ . '/../../helpers/network.php' );

class SBRS_RPC_Message_Content_Provider
{
    private $postUrl;
    /** @var  SBRS_WP */
    private $wp;

    function __construct($wp, $postUrl)
    {
        $this->wp=$wp;
        $this->postUrl = $postUrl;
    }

    public function getMessageContent($messageID)
    {
        if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec') && function_exists('curl_error')
            && function_exists('curl_getinfo') && function_exists('curl_close')
        )
            return $this->getMessageContentViaCurlLib($messageID);
        if (function_exists('fopen') && function_exists('stream_context_create'))
            return $this->getMessageContentViaStreams($messageID);

        return $this->getMessageViaWPRemotePost($messageID);
    }

    private function getMessageContentViaCurlLib($messageID)
    {
        $params = array('code' => $messageID, 'sourceIP' => SBRS_Helper_Network::get_real_ip_address());


        $ch = curl_init();

        if (FALSE === $ch)
            SBRS_Helper_Fail::bye('failed to initialize');

        curl_setopt($ch, CURLOPT_URL, $this->postUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


        $server_output = curl_exec($ch);

        if (FALSE === $server_output)
            SBRS_Helper_Fail::bye(curl_error($ch));

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
            SBRS_Helper_Fail::bye("Request to " . $this->postUrl . ' failed. Response code : ' . curl_getinfo($ch, CURLINFO_HTTP_CODE), null);
        }

        curl_close($ch);

        return $server_output;

    }

    private function getMessageContentViaStreams($messageID)
    {

        $params = array('code' => $messageID, 'sourceIP' => SBRS_Helper_Network::get_real_ip_address());
        $params = http_build_query($params);

        $context_options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                    . "Content-Length: " . strlen($params) . "\r\n",
                'content' => $params
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )

        );
        $context = stream_context_create($context_options);
        $stream = @fopen($this->postUrl, 'r', false, $context);
        if ($stream === False) {
            $error = error_get_last();
            if (isset($error['message'])) {
                $message = $error['message'];
                unset($error['message']);
            } else {
                $message = "Request failed";
            }
            SBRS_Helper_Fail::bye($message, $error);
        }
        $data = stream_get_contents($stream);

        fclose($stream);

        return $data;


    }

    private function getMessageViaWPRemotePost($messageID)
    {
        $params = array('code' => $messageID, 'sourceIP' => SBRS_Helper_Network::get_real_ip_address());

        $res = $this->wp->wp_remote_post($this->postUrl, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'sslverify' => false,
            'body' => $params,
            'cookies' => array()
        ));

        if (!$this->wp->is_wp_error($res)) {
            if (!isset($res['response']) || !isset($res['response']['code']) || $res['response']['code'] != 200) {
                $message = 'Request to ' . $this->postUrl . ' failed. Response Code: ' . $res['response']['code'];
                if (isset($res['response']['message'])) {
                    $message = $message . '. Message: ' . $res['response']['message'];
                }
                unset($res['response']);
                SBRS_Helper_Fail::bye($message, $res);
            }
            if (isset($res['body'])) {
                return $res['body'];
            }
        } else {
            SBRS_Helper_Fail::bye($res->get_error_message(), $res->get_error_data());
        }
    }




}
