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

require_once( __DIR__ . '/security-scanner-engine.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );

class SBRS_Scanner_Engine_Factory
{
    /** @var  SBRS_Settings */
    private $settings;
    /** @var  SBRS_WP */
    private $wp;

    public function __construct(SBRS_Settings $settings, SBRS_WP $wp)
    {
        $this->settings = $settings;
        $this->wp = $wp;
    }

    public function create($type, $scan)
    {
        switch ($type) {
            case'security':
                return $this->get_security_engine($scan);
            default:
                return $this->get_security_engine($scan);
        }
    }

    private function get_security_engine($scan)
    {
        return new SBRS_Security_Scanner_Engine($scan, $this->settings, $this->wp);
    }


}
