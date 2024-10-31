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

require_once( __DIR__ . '/checker.php');
require_once( __DIR__ . '/event-manager.php');
require_once( __DIR__ . '/settings.php');
require_once( __DIR__ . '/wp.php');
require_once( __DIR__ . '/modules/activation.php');
require_once( __DIR__ . '/modules/cron.php');
require_once( __DIR__ . '/modules/lifecycle.php');
require_once( __DIR__ . '/modules/rpc.php');
require_once( __DIR__ . '/modules/security.php');

class SBRS_Plugin extends SBRS_Module
{


    const VERSION = '0.1';
    const PREFIX = 'sabres_';


    protected $modules = array();
    /** @var  SBRS_WP */
    private $wp;
    /** @var  SBRS_Settings */
    private $settings;
    /** @var  SBRS_Event_Manager */
    private $event_manager;
    /** @var  SBRS_RPC */
    private $rpc;
    /** @var  SBRS_Activation */
    private $activation;

    /** @var  SBRS_Module */
    private $traffic;

    /** @var  SBRS_Module */
    private $firewall;
    /** @var  SBRS_Security */
    private $security;
    /** @var  SBRS_Cron */
    private $cron;

    /** @var  SBRS_Module */
    private $tfa;
    /** @var  SBRS_Lifecycle */
    private $lifecycle;
    /** @var  SBRS_Checker */
    private $checker;
    /** @var  SBRS_Admin */
    private $admin;

    private $plugin_base;

    private $auto_update;

    function __construct($plugin_base, $wp, $settings, $event_manager, $rpc, $activation, $traffic, $firewall, $security, $cron, $tfa, $lifecycle, $checker, $admin
      ,$auto_update)
    {
        $this->plugin_base = $plugin_base;
        $this->wp = $wp;
        $this->settings = $settings;
        $this->rpc = $rpc;
        $this->event_manager = $event_manager;
        $this->activation = $activation;
        $this->traffic = $traffic;
        $this->firewall = $firewall;
        $this->security = $security;
        $this->cron = $cron;
        $this->tfa = $tfa;
        $this->lifecycle = $lifecycle;
        $this->checker = $checker;
        $this->admin = $admin;
        $this->auto_update=$auto_update;

        $this->modules = array(
            $this->rpc,
            $this->activation,
            $this->traffic,
            $this->firewall,
            $this->security,
            $this->cron,
            $this->tfa,
            $this->lifecycle,
            $this->admin,
            $this->auto_update
        );
    }

    /**
     * @param SBRS_Event_Manager $manager
     */
    public function run(SBRS_Event_Manager $manager)
    {
        /** @var SBRS_Module $module */
        foreach ($this->modules as $module) {
            if ($this->is_enable($module)) {
                $module->run($manager);
            }

        }
    }

    public function is_enable(SBRS_Module $module)
    {
        $keyStatus = 'mod_' . $module->get_name() . '_status';
        $status = $this->settings->$keyStatus;
        if ($status == 'off') {
            return false;
        }
        return true;
    }

    public static function load_resources()
    {

    }

    protected static function clear_caching_plugins()
    {
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        // W3 Total Cache
        if (class_exists('W3_Plugin_TotalCacheAdmin')) {
            $w3_total_cache = w3_instance('W3_Plugin_TotalCacheAdmin');

            if (method_exists($w3_total_cache, 'flush_all')) {
                $w3_total_cache->flush_all();
            }
        }
    }


    public function activate()
    {
        $this->upgradeLoggerDb();
        $this->single_activate();
    }


    protected function single_activate()
    {
        $broken_modules = array();
        $side_modules = array('tfa', 'security');
        /** @var SBRS_Module $module */
        foreach ($this->modules as $module) {
            $errors = $this->checker->test($module->get_checker_tests());

            if (is_array($errors)) {
                $broken_modules[$module->get_name()] = $errors;
                $this->disable_module($module->get_name(), $errors);
            }
        }
        foreach ($broken_modules as $module_name => $errors) {
            if (!in_array($module_name, $side_modules)) {
                $this->wp->deactivate_plugins(SABRES_PLUGIN_BASE_NAME, true);
                break;
            }
        }
        /** @var SBRS_Module $module */
        foreach ($this->modules as $module) {
            $module->activate();
        }

        $this->wp->flush_rewrite_rules(true);
    }

    public function disable_module($name, $error)
    {
        $keyStatus = 'mod_' . $name . '_status';
        $keyErrors = 'mod_' . $name . '_errors';
        $this->settings->{$keyStatus} = 'off';
        $this->settings->{$keyErrors} = json_encode($error);
    }

    public function deactivate()
    {
        /** @var SBRS_Module $module */
        foreach ($this->modules as $module) {
            $module->deactivate();
        }

        $this->wp->flush_rewrite_rules(true);
    }


    public function register_hook_callbacks()
    {
        $this->wp->register_activation_hook($this->plugin_base, array($this, 'activate'));
        $this->wp->register_deactivation_hook($this->plugin_base, array($this, 'deactivate'));
        $this->wp->add_action('wp_enqueue_scripts', array(__CLASS__, 'load_resources'));
        $this->wp->add_action('admin_enqueue_scripts', array(__CLASS__, 'load_resources'));
        $this->wp->add_action('granted_super_admin', array($this, 'privilege_granted'));
        $this->wp->add_action('set_user_role', array($this, 'set_user_role'), 10, 3);
        $this->wp->add_action('add_user_role', array($this, 'add_user_role'), 10, 2);
        $this->wp->add_action('wp_login_failed', array($this, 'login_failed'), 10, 1);
        $this->wp->add_action('user_register', array($this, 'user_register'), 10, 1);
        $this->wp->add_action('wp_login', array($this, 'hook_wp_login'), 10, 2);
        //$this->wp->add_action('init', array($this, 'upgrade'), 11);
    }

    public function privilege_granted($user_id)
    {
        $this->event_manager->event_trigger('privilege.grant', array($user_id));
    }

    public function set_user_role($user_id, $role, $old_roles)
    {
        $this->event_manager->event_trigger('set.user.role', array($user_id, $role, $old_roles));
    }

    public function login_failed($username)
    {
        $this->event_manager->event_trigger('login.failed', array($username));
    }

    public function user_register($user_id)
    {
        $this->event_manager->event_trigger('user.register', array($user_id));
    }

    public function add_user_role($user_id, $role)
    {
        $this->event_manager->event_trigger('add.user.role', array($user_id, $role));
    }

    public function hook_wp_login($user_login, $user)
    {
        $this->event_manager->event_trigger('login.success', array($user_login, $user));
    }

    public function upgrade($db_version = 0)
    {
        /** @var SBRS_Module $module */
        foreach ($this->modules as $module) {
            $module->upgrade($db_version);
        }


        self::clear_caching_plugins();
    }

    public static function upgradeLoggerDb()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $wpdb->hide_errors();

        $table_name = $wpdb->prefix . 'sbs_log';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          log_type varchar(100) NOT NULL,
          logger varchar(100) NOT NULL,
          message longtext NOT NULL,
          log_data longtext,
          backtrace longtext,
          created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (ID)
			) $charset_collate;";
        @dbDelta($sql);
    }
}
