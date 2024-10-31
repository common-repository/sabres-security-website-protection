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

require_once( __DIR__ . '/engine.php');
require_once( __DIR__ . '/../../request.php');
require_once( __DIR__ . '/../../settings.php');
require_once( __DIR__ . '/../../wp.php');

class SBRS_Firewall_Engine_Factory
{

    /** @var SBRS_Settings */
    private $settings;
    /** @var SBRS_WP */
    private $wp;

    /** @var  SBRS_Request */
    private $request;

    private $traffic_dispatcher;

    private $server;


    public function __construct(SBRS_Settings $settings, SBRS_WP $wp, $request, $traffic_dispatcher, $server)
    {
        $this->settings = $settings;
        $this->wp = $wp;
        $this->request = $request;
        $this->traffic_dispatcher = $traffic_dispatcher;
        $this->server = $server;
    }

    public function create($type)
    {
        switch ($type) {
            case'engine':
                return $this->get_engine();
            default:
                return $this->get_engine();
        }
    }

    private function get_engine()
    {
        return new SBRS_Firewall_Engine($this->settings, $this->wp, $this->request, $this->traffic_dispatcher, $this->server);
    }


}
