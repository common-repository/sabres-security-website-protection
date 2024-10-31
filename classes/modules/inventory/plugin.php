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

require_once( __DIR__ . '/base.php' );

class SBRS_Inventory_Plugin extends SBRS_Inventory_Base
{

    public function get_inventory($files = null, $exts)
    {

        $get_files = (isset($files) && strcasecmp(trim($files), 'true') == 0);

        $plugins = $this->wp->get_plugins();
        foreach ($plugins as $plugin_file => &$plugin_attr) {
            $plugin_attr['Active'] = $this->wp->is_plugin_active($plugin_file);
            $plugin_attr['path'] = dirname($this->inventory_files->find_relative_path(ABSPATH, $this->wp->get_wp_plugin_dir() . '/' . $plugin_file));

            if ($get_files) {
                $files = array();
                $result_files = null;
                if (stripos($plugin_file, '/') !== false)
                    $result_files = $this->inventory_files->get_files($this->wp->get_wp_plugin_dir() . '/' . dirname($plugin_file), $exts, true);
                else
                    $result_files = array($this->wp->get_wp_plugin_dir() . '/' . $plugin_file);

                list($result_files, $failed_files) = $this->inventory_files->exclude_no_readable($result_files);

                if (count($failed_files)) {
                    $this->logger->log('warning', "RPC", "Plugins inventory", implode(", ", $failed_files));
                }

                foreach ($result_files as $result_file) {
                    if (!is_dir($result_file)) {
                        $file_data = array(
                            'Name' => str_replace($this->wp->get_wp_plugin_dir() . '/', '', $result_file));
                        $this->inventory_files->calc_md5_file($result_file, $file_data);
                        $files[] = $file_data;
                    }
                }
                $plugin_attr['Files'] = $files;
            }
        }

        return $plugins;
    }


}
