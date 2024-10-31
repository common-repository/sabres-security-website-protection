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

final class SBRS_Firewall_Check_Url
{

    private $patterns = array(
        '[a-zA-Z0-9_]=http://',
        '[a-zA-Z0-9_]=(\.\.//?)+',
        '[a-zA-Z0-9_]=/([a-z0-9_.]//?)+',
        '\=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        '(\.\./|%2e%2e%2f|%2e%2e/|\.\.%2f|%2e\.%2f|%2e\./|\.%2e%2f|\.%2e/)',
        '(..%c0%af|..%c1%9c)',
        'ftp\:',
        'http\:',
        'https\:',
        '\=\|w\|',
        '^(.*)/self/(.*)$',
        '^(.*)cPath=http://(.*)$',
        'base64_encode.*\(.*\)',
        'base64_(en|de)code[^(]*\([^)]*\)',
        'GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]',
        '_REQUEST(=|\[|\%[0-9A-Z]{0,2}) [OR]',
        '^.*(\(|\)|<|>|%3c|%3e).*',
        '^.*(\x00|\x04|\x08|\x0d|\x1b|\x20|\x3c|\x3e|\x7f).*',
        '(\.{1,}/)+(motd|etc|bin)',
        //'(localhost|loopback|127\.0\.0\.1)',
        //'(<|>|["\']|%0A|%0D|%27|%3C|%3E|%00)',
        '(%0A|%0D|%00)',
        '\-[sdcr].*(allow_url_include|allow_url_fopen|safe_mode|disable_functions|auto_prepend_file)',
    );

    public function __construct()
    {
    }    

    public function check_request()
    {
        if (!empty($_SERVER['QUERY_STRING'])) {
            $query_string = $_SERVER['QUERY_STRING'];

            foreach ($this->patterns as $pattern) {
                if (preg_match("~$pattern~iu", $query_string)) {
                    return true;
                }
            }
        }
    }

}
