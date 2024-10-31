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

require_once( __DIR__ . '/auth.php' );
require_once( __DIR__ . '/form.php' );
require_once( __DIR__ . '/hooks.php' );
require_once( __DIR__ . '/settings.php' );
require_once( __DIR__ . '/../../logger.php' );
require_once( __DIR__ . '/../../request.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );

class SBRS_TFA_Implementation {
    /** @var  SBRS_WP */
    private $wp;
    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_Logger */
    private $logger;
    /** @var  SBRS_Request */
    private $request;

    private $auth_settings;
    private $auth_form;
    private $auth;
    private $hooks;

    public function __construct($ext_url_provider,$wp, $settings, $logger, $request) {
        $this->wp = $wp;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->request = $request;
        $this->auth_settings = new SBRS_TFA_Settings($this->wp);
        $this->auth_form = new SBRS_TFA_Form($this->wp);
        $this->auth = new SBRS_TFA_Auth($ext_url_provider,$this->auth_settings, $this->wp, $this->logger, $this->request, $this->settings);
        $this->hooks = new SBRS_TFA_Hooks($this->wp, $this->settings, $this->auth, $this->auth_form);
    }

    public function getHooks() {
        return $this->hooks;
    }

    public function get_tfa_settings() {
        return $this->auth_settings;
    }
}
