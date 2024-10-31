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

class SBRS_Helper_Utils
{

    static public function associated_array_to_obj($arr)
    {
        $obj = (object)$arr;
        foreach ($arr as &$value) {
            if (is_array($value))
                $value = (object)$value;
        }
        return $obj;
    }

    static public function obj_to_associated_array($obj)
    {
        $arr = (array)$obj;
        foreach ($arr as &$value) {
            if (is_object($value))
                $value = (array)$value;
        }
        return $arr;
    }

    static public function get_json($arr)
    {
        return json_encode(self::associated_array_to_obj($arr));
    }

    public static function array_intersect_assoc_key($arr, $keys)
    {
        return array_intersect_key(
            array_change_key_case($arr, CASE_LOWER),
            array_change_key_case(array_flip($keys), CASE_LOWER)
        );
    }

    public static function array_diff_assoc_key($arr, $keys)
    {
        return array_diff_key(
            array_change_key_case($arr, CASE_LOWER),
            array_change_key_case(array_flip($keys), CASE_LOWER)
        );
    }

    public static function array_refactor_keys($arr, $prefix)
    {
        $ret = array();

        foreach ($arr as $key => $value) {
            $newKey = $prefix . $key;
            $ret[$newKey] = $value;
        }

        return $ret;
    }

    public static function array_utf8_encode(&$arr)
    {
        array_walk_recursive($arr, function (&$item, $key) {
            $item = utf8_encode($item);
        });

        return $arr;
    }

    static public function debug_log($str)
    {
        file_put_contents(dirname(dirname(__FILE__)) . '/debug.log', date("Y-m-d H:i:s") . " - $str\n", FILE_APPEND);
    }

    public static function to_hex($str)
    {
        $hex = '';

        for ($i = 0; $i < strlen($str); $i++) {
            $ord = ord($str[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0' . $hexCode, -2);
        }

        return $hex;
    }

    static public function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        || (defined('DOING_AJAX') && DOING_AJAX);
    }

    public static function is_integer($string, &$result)
    {
        $result = null;
        if (!is_numeric($string))
            return false;
        $intVal = intVal($string);
        if ($intVal != floatVal($string))
            return false;
        $result = $intVal;
        return true;

    }

    public static function friendly_error_type($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    }

    public static function string_ends_with($haystack, $needle) {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

}
