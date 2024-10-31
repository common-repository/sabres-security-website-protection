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

abstract class SBRS_Helper_Datetime {
    public static function get_microtime_string( ) {
        $time = microtime( true );
        $micro = sprintf( "%03d", ( $time - floor( $time ) ) * 1000 );

        return gmdate( 'Y-m-d H:i:s.', $time ) . $micro ;
    }
}
