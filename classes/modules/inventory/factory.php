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

require_once( __DIR__ . '/core.php' );
require_once( __DIR__ . '/files.php' );
require_once( __DIR__ . '/plugin.php' );
require_once( __DIR__ . '/themes.php' );
require_once( __DIR__ . '/themes2.php' );
require_once( __DIR__ . '/../../logger.php' );
require_once( __DIR__ . '/../../wp.php' );

class SBRS_Inventory_Factory
{

    /** @var SBRS_WP */
    private $wp;
    /** @var SBRS_Logger */
    private $logger;
    /** @var  SBRS_Inventory_Files */
    private $inventory_files;

    public function __construct(SBRS_WP $wp, SBRS_Logger $logger, SBRS_Inventory_Files $inventory_files)
    {
        $this->wp = $wp;
        $this->logger = $logger;
        $this->inventory_files = $inventory_files;
    }

    public function create($type)
    {
        switch ($type) {
            case'core':
                return $this->get_core_inventory();
            case'plugins':
                return $this->get_plugin_inventory();
            case'themes':
                return $this->get_theme_inventory();
            case'themes2':
                return $this->get_theme2_inventory();
            default:
                return $this->get_core_inventory();
        }
    }

    private function get_core_inventory()
    {
        return new SBRS_Inventory_Core($this->wp, $this->logger, $this->inventory_files);
    }


    private function get_plugin_inventory()
    {
        return new SBRS_Inventory_Plugin($this->wp, $this->logger, $this->inventory_files);
    }


    private function get_theme_inventory()
    {
        return new SBRS_Inventory_Themes($this->wp, $this->logger, $this->inventory_files);
    }


    private function get_theme2_inventory()
    {
        return new SBRS_Inventory_Themes2($this->wp, $this->logger, $this->inventory_files);
    }


}
