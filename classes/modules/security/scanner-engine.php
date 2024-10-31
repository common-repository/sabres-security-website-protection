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

class SBRS_Scanner_Engine
{
    protected $settings;
    protected $current_scan;
    protected $started_at;
    protected $ended_at;

    protected $_abs_path;
    protected $_event_callbacks = array();
    protected $_memory_limit;

    public function __construct($scan)
    {
        $this->current_scan = $scan;

        $this->_abs_path = ABSPATH;
    }

    public function register_event_callback($event_name, $callback)
    {
        if (!isset($this->_event_callbacks[$event_name])) {
            $this->_event_callbacks[$event_name] = array();
        }

        array_push($this->_event_callbacks[$event_name], $callback);
    }

    protected function event_trigger($event_name, $args)
    {

        if (!empty($this->_event_callbacks[$event_name])) {
            $callbacks = $this->_event_callbacks[$event_name];

            foreach ($callbacks as $callback) {
                if (!empty($args)) {
                    call_user_func_array($callback, $args);
                } else {
                    call_user_func($callback);
                }
            }
        }
    }

    protected function init($settings = null)
    {
        $this->settings = (object)array_change_key_case($settings, CASE_LOWER);
    }

    public function is_valid()
    {
        $valid = true;

        if (isset($this->current_scan)) {
            $valid = false;
        }

        return $valid;
    }

    public function start()
    {
        $this->_memory_limit = memory_get_usage(true);

        $this->started_at = time();
        $this->ended_at = null;

        $this->event_trigger('status', array(
            'status' => 'started'
        ));

        $this->event_trigger('status', array(
            'status' => 'running'
        ));
    }

    protected function end()
    {
        $this->ended_at = time();

        $this->event_trigger('status', array(
            'status' => 'ended'
        ));
    }

    protected function fix_issue($code, $unique_id)
    {
    }

}
