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


abstract class SBRS_Helper_Parser
{

    public static function get_function_pattern($functions)
    {
        if (!empty($functions)) {
            $pattern = '';

            if (!empty($functions)) {
                $functions_string = implode('|', $functions);
                $pattern = "(?:^|[^0-9a-zA-Z_])+(?:$functions_string)[ ;]*" . '\((?:[ \"\']*([^\"\']*)[ \"\']*)\)*';
            }

            if (!empty($pattern)) {
                return "~$pattern~i";
            }
        }
    }

    public static function get_functions($content, $function_name, $leading_arg = null)
    {
        $matches = array();

        preg_match_all("/$function_name\s*\(\s*(.*)\s*\)\s*;/", $content, $matches, PREG_OFFSET_CAPTURE);

        if (isset($leading_arg)) {
            $results = array();

            list($functions_content, $functions_args) = $matches;

            for ($i = 0; $i < count($functions_content); $i++) {
                if (!empty($functions_content[$i]) && !empty($functions_args[$i])) {
                    $function_args = $functions_args[$i][0];

                    $function_leading_arg = current(explode(',', $function_args));

                    if (strcasecmp($function_leading_arg, $leading_arg) == 0) {
                        array_push($results, $functions_args[$i]);
                    }
                }
            }
            $matches = $results;
        }

        return $matches;


    }
}
