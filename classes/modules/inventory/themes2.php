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

require_once( __DIR__ . '/base.php');

class SBRS_Inventory_Themes2 extends SBRS_Inventory_Base
{
    public function get_inventory($files = null, $exts = array())
    {
        $ret = array(
            'current' => $this->wp->get_stylesheet(),
            'themes' => array()
        );

        $getFiles = (isset($files) && strcasecmp(trim($files), 'true') == 0);
        $themes = $this->wp->wp_get_themes();
        $props = array('Name', 'ThemeURI', 'Description', 'Author', 'AuthorURI', 'Version', 'Template', 'Status', 'Tags', 'TextDomain');

        foreach ($themes as $theme_root => $theme_object) {
            $theme = array();

            foreach ($props as $prop) {
                $theme[$prop] = $theme_object->get($prop);
            }
            $theme['path'] = $this->inventory_files->find_relative_path(ABSPATH, $theme_object->get_template_directory());
            if ($getFiles) {
                $theme['Files'] = array();

                $theme_files = array();
                if (is_array($exts)) {
                    foreach ($exts as $ext) {
                        $theme_files = array_merge($theme_files, $theme_object->get_files(substr($ext, 1), -1));
                    }
                } else
                    $theme_files = array_merge($theme_files, $theme_object->get_files(substr(null, 1), -1));

                list($theme_files, $failed_files) = $this->inventory_files->exclude_no_readable($theme_files);

                if (count($failed_files)) {
                    $this->logger->log('warning', "RPC", "Themes2 inventory", implode(", ", $failed_files));
                }

                foreach ($theme_files as $theme_file) {
                    $file_name = ltrim(str_replace(rtrim(ABSPATH, '/\\'), '', $theme_file), '/\\');
                    $file_name = str_replace('\\', '/', $file_name);

                    $file_data = array('fullPath' => $file_name);
                    $this->inventory_files->calc_md5_file($theme_file, $file_data);
                    $theme['Files'][] = $file_data;
                }
            }
            $ret['themes'][$theme_root] = $theme;
        }

        return $ret;
    }
}