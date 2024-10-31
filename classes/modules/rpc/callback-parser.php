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

class SBRS_RPC_Callback_Parser
{

    public function parseRequest()
    {
        if (!isset($_GET) || empty($_GET) || !isset($_GET["message-id"])) {
            header('HTTP/1.1 500 Internal Server Error');
            return null;
        }
        return array('message-id' => $_GET["message-id"]);
    }

    public function parse_message($message)
    {
        $ret = array();
        $urlParts = explode('&', $message);

        foreach ($urlParts as $part) {
            $segments = explode('=', $part, 2);
            $value = null;
            if (isset($segments[1]))
                $value = $segments[1];

            if (count($segments) > 1) {
                $ret[$segments[0]] = $value;
            }
        }

        return $ret;
    }
}
