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

require_once( __DIR__ . '/logger.php');
require_once( __DIR__ . '/wp.php');
require_once( __DIR__ . '/helpers/hash.php');
require_once( __DIR__ . '/helpers/utils.php');

///
class SBRS_Settings
{
    private static $settingsOption = 'sabres-settings';
    private static $default_settings = array(
        'websiteSabresServerToken' => '',
        'websiteSabresClientToken' => '',
        'https' => 'True',
        'maxDepth' => 4,
        'isActive' => 'False',
        'addedInPortal' => 'False',
        // 'LicenseType' => NULL, // 'FREE' | 'LIGHT' | 'MEDIUM' | 'HEAVY'
        'debug' => 'False',
        'mod_tfa_active' => 'False',
        'mod_firewall_active' => 'True',
        'mod_firewall_country_mode' => 'allow_all',
        'mod_firewall_xml_rpc' => 'True',
        'mod_firewall_fake_crawler' => 'True',
        'mod_firewall_known_attacks' => 'True',
        'mod_firewall_waf' => 'False',
        'mod_firewall_human_detection' => 'True',
        'mod_firewall_spam_registration' => 'True',
        'mod_firewall_anon_browsing' => 'True',
        'mod_admin_protection' => 'False',
        'mod_suspicious_login' => 'False',
        'mod_brute_force' => 'False',
        'mod_scheduled_scans' => 'False',
        'mod_malware_clean' => 'False',
        'mod_analyst_service' => 'False',
        'brute_force_threshold' => 7,
        'canScheduleScans' => 'False',
        'token' => '',
        'symmetricEncryptionKey' => '',
        'verifyHashSalt' => '',
        'apiKey' => '',
        'version_number' => '',
        'click_jacking' => 'True',
        'login_errors' => 'False',
        'author_archive' => 'False',
        'wp_api_keys_url' => 'https://api.wordpress.org/secret-key/1.1/salt/',
        'auto_update' => 'False',
        'error_handling' => 'False',
        'debug_mode' => 'False',
        'force_ssl' => 'False',
        'preInstall' => 'False',
        'block_unauthorized_RPC_IPs' => 'False',
        'authorized_RPC_IPs' => array(),
        'first_activation' => 'True',
        'isPremiumCustomer' => 'False',
        'sso_email' => '',
        'sso_user' => '',
        'scheduled_scan_time' => '00:00',
        'scheduled_scan_interval' => '3',
        'ts_first_noticed' => 0,
        'ts_last_noticed' => 0,
        'ts_last_visited' => 0,
        'triggerSyncSettings'=>'True'
    );
    private $settings;
    /** @var  SBRS_WP */
    private $wp;
    /** @var  SBRS_Logger */
    private $logger;


    private function init()
    {
        $this->settings = self::$default_settings;
        $this->settings['apiKey'] = SBRS_Helper_Hash::generate_hash();
        $this->settings['token'] = SBRS_Helper_Hash::generate_hash();
        $this->settings['symmetricEncryptionKey'] = SBRS_Helper_Hash::generate_hash();
        $this->settings['verifyHashSalt'] = SBRS_Helper_Hash::generate_hash();

    }

    /// Construct and load the settings.
    public function __construct($wp, $logger)
    {

        $this->wp = $wp;
        $this->logger = $logger;
        $this->init();

        $this->load_settings();
    }

    /////////////////////////////////////////
    // Public:

    /// Get value by key.
    public function __get($key)
    {

        return array_key_exists($key, $this->settings) ? $this->settings[$key] : '';
    }

    /// Set Key's value.
    public function __set($key, $value)
    {

        $this->settings[$key] = $value;

        return $this->store_settings();
    }

    public function should_trigger_activation()
    {
        return $this->websiteSabresServerToken == '' || $this->websiteSabresClientToken == '';
    }

    /// Set an array of values and store them.
    public function set_values($sets)
    {
        foreach ($sets as $key => $value) {
            $this->settings[$key] = $value;
        }
        return $this->store_settings();
    }

    /// Get the settings as a JSON string
    public function get_json($fields = null)
    {
        if (!empty($fields)) {
            $settings = array_intersect_key($this->settings,
                array_flip($fields));
        } else {
            $settings = $this->settings;
        }

        return SBRS_Helper_Utils::get_json($settings);
    }

    public function get_settings($prefix = null)
    {
        $results = array();

        foreach ($this->settings as $key => $value) {
            if (stripos($key, $prefix) === 0) {
                $new_key = substr($key, strlen($prefix) + 1);
                $results[$new_key] = $this->settings[$key];
            }
        }

        return $results;
    }

    public function get_default_settings( )
    {
        return self::$default_settings;
    }

    public function reload()
    {
        $this->wp->wp_cache_delete('alloptions', 'options');
        $this->wp->wp_cache_delete(self::$settingsOption, 'options');
        $this->init();
        $this->load_settings();
    }

    public function reset($merge_array = null)
    {
        $this->init();
        if (!empty($merge_array))
            $this->settings = array_merge($this->settings, $merge_array);
        $this->store_settings();
    }

    /////////////////////////////////////////
    // Private:

    private function load_settings()
    {
        $this->load_from_wordpress_options();
    }

    private function load_from_wordpress_options()
    {
        $wp_sbr_options = $this->wp->get_option(self::$settingsOption);
        if (empty($wp_sbr_options)) {
            $this->store_settings();
            return false;
        }

        $sbr_settings = SBRS_Helper_Utils::obj_to_associated_array(json_decode($wp_sbr_options));
        if (!empty($sbr_settings)) {
            $this->settings = array_merge($this->settings, $sbr_settings);
            //$this->store_settings();
        }

        return true;
    }

    private function store_settings()
    {
        if (empty($this->settings))
            return false;

        $json = $this->get_json();
        $this->wp->update_option(self::$settingsOption, $json);

        return $json;
    }

    private function php_array_to_str($arr)
    {
        $str = '';
        foreach ($arr as $key => $value) {
            $str .= "\t'$key' => ";
            $str .= is_array($value) ? $this->php_array_to_str($value) : "'$value'";
            $str .= ",\n";
        }
        return "array(\n$str)";
    }
}
