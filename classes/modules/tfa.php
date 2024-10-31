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

require_once( __DIR__ . '/tfa/implementation.php' );
require_once( __DIR__ . '/../module.php' );
require_once( __DIR__ . '/../settings.php' );
require_once( __DIR__ . '/../wp.php' );

class SBRS_Tfa extends SBRS_Module {
    /** @var SBRS_Settings  */
    private $settings;
    /** @var SBRS_WP  */
    private $wp;
    /** @var  SBRS_TFA_Implementation */
    private $implementation;

    public static $is_tfa_logging_in = false;
    public static $is_login_success = false;

    public function __construct($wp, $settings, $implementation) {
        $this->settings = $settings;
        $this->wp = $wp;
        $this->implementation = $implementation;
    }

    public function activate()
    {
        // TODO: Implement activate() method.
    }

    public function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    public function register_hook_callbacks()
    {
        $hooks = $this->implementation->getHooks();
        $this->wp->add_action('wp_authenticate', array($hooks, 'wp_authenticate'), 1, 1);
        $this->wp->add_filter('authenticate', array($hooks, 'filter_authenticate'), 99, 1);
        $this->wp->add_action('login_form', array($hooks, 'login_form'));
        $this->wp->add_action('woocommerce_login_form', array($hooks, 'login_form'));
        $this->wp->add_action('init', array($this, 'buffer_start'));
    }

    public function buffer_start()
    {
        if ( (strcasecmp( $this->settings->mod_tfa_active, 'true' ) == 0) && $_SERVER['REQUEST_METHOD'] === 'POST' && !is_user_logged_in() ) {
            $wp_login = SBRS_Helper_Network::get_request_path(preg_replace('~https?://.+?(/.*)~i','\1',wp_login_url()));
            $woocommerce_login = get_permalink( get_option('woocommerce_myaccount_page_id') );

            $req_uri = SBRS_Helper_Network::get_request_path();

            if ((!empty($req_uri) && $wp_login===$req_uri) ||
                (!empty($woocommerce_login) && strpos($woocommerce_login, $req_uri) !== false)) {
                @ob_start();
                self::$is_tfa_logging_in = true;
            }
        }
    }

    public function upgrade($db_version = 0)
    {
        // TODO: Implement upgrade() method.
    }

    public function is_enabled()
    {

        return strcasecmp($this->settings->mod_tfa_active, 'true') === 0;
    }
}
