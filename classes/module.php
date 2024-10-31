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

require_once( __DIR__ . '/event-manager.php');

abstract class SBRS_Module
{

    public function __construct()
    {
    }

    public function is_enabled()
    {


        return true;
    }

    public function run(SBRS_Event_Manager $manager)
    {
        if ($this->is_enabled()) {
            $this->register_hook_callbacks();
            $this->register_events($manager);
        }
    }

    public function register_events(SBRS_Event_Manager $manager)
    {

    }

    public function activate()
    {

    }

    public function deactivate()
    {

    }

    public function register_hook_callbacks()
    {

    }

    public function upgrade($db_version = 0)
    {

    }

    public function get_name()
    {
        return strtolower(substr(strstr(get_class($this), "_"), 1));
    }

    public function get_checker_tests()
    {
        return array();
    }
}
