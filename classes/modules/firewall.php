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

require_once( __DIR__ . '/firewall/engine.php' );
require_once( __DIR__ . '/firewall/engine-factory.php' );
require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../settings.php' );

class SBRS_Firewall extends SBRS_Module
{

    public static $unique_id;
    public static $unique_id_hash;
    public static $request_unique_id;

    /** @var SBRS_WP */
    private $wp;
    /** @var SBRS_Firewall_Engine */
    private $engine;
    /** @var SBRS_Firewall_Engine_Factory */
    private $factory;

    /** @var  SBRS_Settings */
    private $settings;

    function __construct(SBRS_WP $wp, $settings, $factory)
    {
        $this->wp = $wp;
        $this->factory = $factory;
        $this->settings = $settings;
        $this->engine = $this->factory->create('engine');
    }


    private function update_db()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $wpdb->hide_errors();

        $table_name = $wpdb->prefix . 'sbs_firewall_cookies';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            unique_id varchar(100) NOT NULL,
            do_action varchar(45) NOT NULL,
            description varchar(255) DEFAULT NULL,
            expiry int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_key (unique_id)
        ) $charset_collate;";
        @dbDelta($sql);

        $table_name = $wpdb->prefix . 'sbs_firewall_countries';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code char(2) NOT NULL DEFAULT '',
            do_action varchar(45) NOT NULL,
            description varchar(255) DEFAULT NULL,
            expiry int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_key (code)
        ) $charset_collate;";
        @dbDelta($sql);

        $table_name = $wpdb->prefix . 'sbs_firewall_custom';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            from_ip bigint(20) unsigned NOT NULL,
            to_ip bigint(20) unsigned NOT NULL,
            do_action varchar(45) NOT NULL,
            description varchar(255) DEFAULT NULL,
            expiry int(11) DEFAULT NULL,
            global_rule bit(1) NOT NULL DEFAULT b'0',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_key (from_ip, to_ip)
        ) $charset_collate;";
        @dbDelta($sql);
    }


    public function init()
    {
        $this->engine->process_request();
    }


    public function activate()
    {
        $this->update_db();
    }


    public function deactivate()
    {

    }

    public function upgrade($db_version = 0)
    {

    }

    public function register_hook_callbacks()
    {
        $this->wp->add_action('init', array($this, 'init'), 0);
    }

    public function is_enabled()
    {


        return strcasecmp($this->settings->mod_firewall_active, 'true') === 0;
    }
}
