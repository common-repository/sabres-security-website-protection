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

require_once( __DIR__ . '/../module.php' );

final class SBRS_Security extends SBRS_Module
{


    public function activate()
    {
        $this->update_db();
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

    private function update_db()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $wpdb->hide_errors();

        $table_name = $wpdb->prefix . 'sbs_scans';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            scan_type varchar(200) NOT NULL,
            status varchar(200) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (ID)
        ) $charset_collate;";
        @dbDelta($sql);

        $table_name = $wpdb->prefix . 'sbs_scan_items';
        $ix_name = 'fk_' . $table_name . '_item_parent_idx';
        $fk_name = 'fk_' . $table_name . '_item_parent';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parent_id bigint(20) unsigned NOT NULL,
            item_type varchar(200) NOT NULL,
            item_code varchar(200) NULL,
            item_desc longtext,
            risk_level tinyint(1) unsigned DEFAULT NULL,
            unique_id varchar(32) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (ID),
            KEY $ix_name (parent_id)
        ) $charset_collate;";
        @dbDelta($sql);
    }

    public function getCheckerTests()
    {
        return array('check_php_functions' => array(
            'apache_get_modules',
            'stream_get_filters',
            'stream_get_transports',
            'stream_get_wrappers',
        ));
    }

}
