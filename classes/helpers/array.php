<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
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

if ( !class_exists('SBRS_Helper_Array') ) {

    /**
     * The Sabres Array Class
     *
     * @author Ariel Carmely - Sabres Security Team
     * @package Sabres_Security_Plugin
     * @since 1.0.0
     */
    abstract class SBRS_Helper_Array {

        public static function array_intersect_assoc_key( $arr, $keys )
        {
            return array_intersect_key(
                array_change_key_case( array_unique( $arr, SORT_REGULAR ), CASE_LOWER ),
                array_change_key_case( array_flip( array_unique( $keys, SORT_REGULAR ) ), CASE_LOWER )
            );
        }

        public static function array_diff_assoc_key( $arr, $keys ) {
            return array_diff_key(
                array_change_key_case( array_unique( $arr, SORT_REGULAR ), CASE_LOWER ),
                array_change_key_case( array_flip( array_unique( $keys, SORT_REGULAR ) ), CASE_LOWER )
            );
        }

        public static function array_refactor_keys( $arr, $prefix ) {
            $ret = array();

            foreach ( $arr as $key => $value ) {
                $newKey = $prefix . $key;
                $ret[$newKey] = $value;
            }

            return $ret;
        }

        public static function array_utf8_encode( &$arr ) {
            array_walk_recursive( $arr, function ( &$item, $key ) {
                $item = utf8_encode( $item );
            } );

            return $arr;
        }
    }
}