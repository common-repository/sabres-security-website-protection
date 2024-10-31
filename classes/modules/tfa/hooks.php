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
require_once( __DIR__ . '/../tfa.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );

class SBRS_TFA_Hooks
{

    /** @var  SBRS_WP */
    private $wp;

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_TFA_Auth */
    private $auth;

    /** @var  SBRS_TFA_Form */
    private $form;

    public function __construct($wp, $settings, $auth, $form)
    {
        $this->wp = $wp;
        $this->settings = $settings;
        $this->auth = $auth;
        $this->form = $form;
    }

    public function filter_authenticate($authUser)
    {
        if (!SBRS_Tfa::$is_tfa_logging_in)
            return $authUser;
        if (!empty($authUser) && get_class($authUser)==='WP_User')
            SBRS_Tfa::$is_login_success = true;
        elseif (!isset($_POST['sabres_tfa_keyphrase']))
           return $authUser;

        $user = (isset($_POST['wp-user']) ? $_POST['wp-user'] : false);
        $username = $user ? $user->user_login : false;

        $checkTwoFactor = $user && get_class($user) == 'WP_User' && $this->auth->must_authenticate($username, $user);

        if ($checkTwoFactor && (!$this->wp->is_wp_error($authUser) || $this->wp->get_sabres_user_meta($user->ID, 'auth_tfa', true))) {
            return $this->auth->authenticate($user, $username, $authUser);
        }

        return $authUser;
    }

    public function wp_authenticate(&$username)
    {
        if (!SBRS_Tfa::$is_tfa_logging_in)
            return;
        if (isset($_POST['wp-auth-id'])) {
            $userID = intval($_POST['wp-auth-id']);

            $user = $this->wp->get_user_by('id', $userID);
            $username = $user->user_login;
            $_POST['wp-user'] = $user;
            return;
        }

        if (!$username) {
            return;
        }

        $user = $this->wp->get_user_by('login', $username);
        if (!$user) {
            $user = $this->wp->get_user_by('email', $username);
        }

        $_POST['wp-user'] = $user;
    }

    public function login_form()
    {
        if (!SBRS_Tfa::$is_tfa_logging_in)
            return;
        if (!SBRS_Tfa::$is_login_success)
            return;
        $existingContents = ob_get_contents();
        if (!is_null($existingContents) && $existingContents) {

            if (!preg_match('/sabres\-two\-factor\-([0-9]+)/', $existingContents, $matches)) {
                return;
            }

            $userID = intval($matches[1]);

            $user = $this->wp->get_user_by('id', $userID);
            if (!$this->wp->is_wp_error($user)) {
                //Strip out the username and password fields
                $formPosition = strrpos($existingContents, '<form');
                $formTagEnd = strpos($existingContents, '>', $formPosition);
                if ($formPosition === false || $formTagEnd === false) {
                    return;
                }

                ob_end_clean();
                ob_start();
                echo substr($existingContents, 0, $formTagEnd + 1);
                $this->wp->set_transient('login_form_hooked', 1);
                $this->form->show($user);
            }
        }

    }
}