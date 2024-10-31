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

class SBRS_Inventory_Core extends SBRS_Inventory_Base
{

    public function get_inventory($files = null, $exts)
    {

        $ret = array(
            'version' => $this->wp->get_wp_version(),
            'files' => array()
        );

        $get_files = (isset($files) && strcasecmp(trim($files), 'true') == 0);

        if ($get_files) {
            $files_inv = $this->inventory_files->get_files(rtrim(ABSPATH, '/\\'), $exts);
            $files_inv = array_merge($files_inv, $this->inventory_files->get_files(ABSPATH . 'wp-admin', $exts, true));
            $files_inv = array_merge($files_inv, $this->inventory_files->get_files(ABSPATH . 'wp-includes', $exts, true));
            $files_inv = array_merge($files_inv, $this->inventory_files->get_files(WP_CONTENT_DIR, $exts));

            $content_dirs = $this->inventory_files->get_dirs(WP_CONTENT_DIR);
            $plugin_path = realpath($this->wp->get_wp_plugin_dir());
            $theme_path = realpath($this->wp->get_theme_root());

            foreach ($content_dirs as $dir) {
                if (is_dir($dir)) {
                    if (realpath($dir) !== $plugin_path && realpath($dir) != $theme_path) {
                        $files_inv = array_merge($files_inv, $this->inventory_files->get_files($dir, $exts, true));
                    }
                }
            }

            list($files_inv, $failed_files) = $this->inventory_files->exclude_no_readable($files_inv);

            if (count($failed_files)) {
                $this->logger->log('warning', "RPC", "Core inventory", implode(", ", $failed_files));
            }

            foreach ($files_inv as $file) {
                if (!is_dir($file)) {
                    $file_name = ltrim(str_replace(rtrim(ABSPATH, '/\\'), '', $file), '/\\');
                    $file_name = str_replace('\\', '/', $file_name);

                    $file_data = array('fullPath' => $file_name);
                    $this->inventory_files->calc_md5_file($file, $file_data);
                    $ret['files'][] = $file_data;
                }
            }
        }

        return $ret;
    }

}
