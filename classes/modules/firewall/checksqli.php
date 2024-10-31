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

final class SBRS_Firewall_Check_Sqli
{    

    private $patterns = array(
        '(NULL|OUTFILE|LOAD_FILE) [OR]',
        'concat[^\(]*\(',
        'union([^s]*s)+elect',
        'union([^a]*a)+ll([^s]*s)+elect',
        '(;|<|>|["\']|["\"]|\)|%0A|%0D|%22|%27|%3C|%3E|%00).*(/\*|union|select|insert|drop|delete|update|cast|create|char|convert|alter|declare|order|["\']script|set|md5|benchmark|encode)',
        '(sp_executesql)'
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
