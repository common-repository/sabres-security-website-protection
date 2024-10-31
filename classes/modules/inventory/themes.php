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

class SBRS_Inventory_Themes extends SBRS_Inventory_Base
{
    public function get_inventory($files = null, $exts = array())
    {
        $getFiles = (isset($files) && strcasecmp(trim($files), 'true') == 0);
        $themes = array();
        $themesObjects = $this->wp->wp_get_themes();
        $props = array('Name', 'ThemeURI', 'Description', 'Author', 'AuthorURI', 'Version', 'Template', 'Status', 'Tags', 'TextDomain');
        foreach ($themesObjects as $themeRoot => $themeObj) {
            $theme = array();
            foreach ($props as $property) {
                $theme[$property] = $themeObj->get($property);
            }
            if ($getFiles) {
                $files = array();
                if (is_array($exts)) {
                    foreach ($exts as $ext) {
                        $files = array_merge($files, $themeObj->get_files(substr($ext, 1), -1));
                    }
                } else
                    $files = array_merge($files, $themeObj->get_files(null, -1));

                list($files, $failed_files) = $this->inventory_files->exclude_no_readable($files);

                if (count($failed_files)) {
                    $this->logger->log('warning', "RPC", "Themes inventory", implode(", ", $failed_files));
                }

                $theme['Files'] = $files;
            }
            $themes[$themeRoot] = $theme;
        }

        return array('current' => $this->wp->get_stylesheet(), 'themes' => $themes);
    }
}