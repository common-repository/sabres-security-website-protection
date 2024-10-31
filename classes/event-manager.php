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

class SBRS_Event_Manager
{
    protected $_event_callbacks =  array();


    public function register_event_callback( $event_name, $callback ) {
        if ( !isset( $this->_event_callbacks[$event_name] ) ) {
            $this->_event_callbacks[$event_name] = array();
        }

        array_push( $this->_event_callbacks[$event_name], $callback );
    }

    public function event_trigger( $event_name, $args ) {

        if ( !empty( $this->_event_callbacks[$event_name] ) ) {
            $callbacks = $this->_event_callbacks[$event_name];

            foreach ( $callbacks as $callback ) {
                if ( !empty( $args ) ) {
                    call_user_func_array( $callback, $args );
                } else {
                    call_user_func( $callback );
                }
            }
        }
    }
}