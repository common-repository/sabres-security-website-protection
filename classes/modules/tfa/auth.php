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

require_once( __DIR__ . '/settings.php' );
require_once( __DIR__ . '/../../logger.php' );
require_once( __DIR__ . '/../../request.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );
require_once( __DIR__ . '/../../helpers/network.php' );
require_once( __DIR__ . '/../../helpers/utils.php' );

class SBRS_TFA_Auth {

    const DEVICE_META_KEY = 'tfa_device';

    const DELIVERY_TYPE_EMAIL = 'email';
    const DELIVERY_TYPE_SMS = 'sms';
    const DELIVERY_TYPE_BOTH = 'both';

    const STRICTNESS_TYPE_NEW_DEVICE='new-device';
    const STRICTNESS_TYPE_EVERY_LOGIN='every-login';

    /** @var  SBRS_TFA_Settings */
    private $settings;
    /** @var  SBRS_WP */
    private $wp;
    /** @var  SBRS_Logger */
    private $logger;

    /** @var  SBRS_Request */
    private $request;

    /** @var  SBRS_Settings */
    private $plugin_settings;

    private $ext_url_provider;

    public function __construct($ext_url_provider,$settings, $wp, $logger, $request, $plugin_settings)
    {
        $this->ext_url_provider=$ext_url_provider;
        $this->settings = $settings;
        $this->wp = $wp;
        $this->logger = $logger;
        $this->request = $request;
        $this->plugin_settings = $plugin_settings;
    }


    public function must_authenticate( $user_login, $user) {

        $user_settings = $this->settings->get_settings($user);
        if ($user_settings['strictness'] == self::STRICTNESS_TYPE_EVERY_LOGIN ||
            $user_settings['strictness'] != self::STRICTNESS_TYPE_NEW_DEVICE) {
            return true;
        }

        $tfa_cookie_name = $this->get_tfa_cookie_name($user_login);
        $tfa_device_id = @$_COOKIE[$tfa_cookie_name];

        if (empty($tfa_device_id) || !$this->validate_tfa_cookie($tfa_device_id)) {
            return true;
        }

        $devices = $this->wp->get_sabres_user_meta( $user->ID, static::DEVICE_META_KEY, true);
        $devices = json_decode( $devices, true );

        if (empty($devices) || !isset($devices[$tfa_device_id])) {
            return true;
        }

        $deviceInfo = $devices[$tfa_device_id];

        $lastLoginStr = isset($deviceInfo['lastLogin']) ? $deviceInfo['lastLogin'] : null;

        if (empty($lastLoginStr)) {
            return true;
        }

        $lastLogin = \DateTime::createFromFormat('Y-m-d H:i:s',$lastLoginStr,new \DateTimeZone('UTC'));

        if (!$lastLogin) {
            return true;
        }

        if (!$user_settings['device-expiry-checked']) {
            return false; //we have a last login with no expiry so he is clear to go
        }

        $lastLoginExpiry = clone $lastLogin;
        $lastLoginExpiry->add(new \DateInterval('P'.$user_settings['device-expiry-days'].'D'));

        if (new \DateTime('now', new \DateTimeZone('UTC')) >$lastLoginExpiry) {
            return true;
        }

        return false;

    }

    public function authenticate($user, $user_login, $authUser) {
        if ( ! isset( $_POST['wp-auth-id'] ) ) {
            if( $this->send_tfa_keyphrase( $user, $user_login ) ) {
                return new \WP_Error('TFA Keyphrase', $this->wp->__('A two factor authentication key was generated and sent to you via email or text message. Enter the key value bellow') . '<!-- sabres-two-factor-' . $user->ID . ' -->');
            } else {
                return new \WP_Error('Error:', $this->wp->__('Error. Failed to send two factor authentication key. Two factor authentication has been turned off. Please contact support for further info'));
            }

        }

        $user = $this->wp->get_userdata( (int) $_POST['wp-auth-id'] );

        if ( ! $user ) {
            return $authUser;
        }

        $auth_tfa_raw = $this->wp->get_sabres_user_meta($user->ID, 'auth_tfa', true);
        $auth_tfa = json_decode($auth_tfa_raw, true);
        if (!empty($auth_tfa)) {

            if (isset($_POST['sabres_tfa_keyphrase'])) {
                // TFA Login
                $keyphrase = $this->wp->sanitize_text_field($_POST['sabres_tfa_keyphrase']);

                if ($keyphrase != '' && $auth_tfa['auth_key'] == $keyphrase) {
                    // Check expiry
                    $timestamp = $this->wp->current_time('timestamp', 1);

                    if ($timestamp < $auth_tfa['expiry']) {
                        // Access granted
                        $user_login = $auth_tfa['cred']['user_login'];

                        $user = $this->wp->get_user_by('login', $user_login);

                        $this->set_tfa_device_cookie($user_login,$user);

                        $this->wp->delete_sabres_user_meta($user->ID, 'auth_tfa');

                        return $user;

                    } else {
                        return new \WP_Error('auth_tfa_key_expired', $this->wp->__('<strong>ERROR</strong>: Keyphrase has expired.'));
                    }
                } else {
                    return new \WP_Error('auth_tfa_key_bad', $this->wp->__('<strong>ERROR</strong>: Wrong keyphrase.') . '<!-- sabres-two-factor-' . $user->ID . ' -->');
                }
            }
        }

        return $authUser;
    }

    public function get_server_api_url()
    {
      if ($this->plugin_settings->https == '' || strcasecmp($this->plugin_settings->https, 'true') == 0) {
          return $this->ext_url_provider->get_base_sagw_url();
      } else {
          return $this->ext_url_provider->get_base_sagw_url_plain();
      }
    }

    private function send_tfa_keyphrase( $user, $user_login ) {

        $body = array(
            'display_name' => $user->display_name,
            'website_server_token' => $this->plugin_settings->websiteSabresServerToken,
            'unique_id' => $this->request->getUniqueID(),
            'ip_address' => SBRS_Helper_Network::get_real_ip_address()
        );

        $user_settings = $this->settings->get_settings($user);

        switch ($user_settings['delivery']) {
            case SBRS_TFA_Auth::DELIVERY_TYPE_EMAIL:
                $body['email'] = $user_settings['email'];
                $body['deliveryType'] = SBRS_TFA_Auth::DELIVERY_TYPE_EMAIL;
                break;
            case SBRS_TFA_Auth::DELIVERY_TYPE_SMS:
                $body['smsNumber'] = $user_settings['smsNumber'];
                $body['deliveryType'] = SBRS_TFA_Auth::DELIVERY_TYPE_SMS;
                break;
            case SBRS_TFA_Auth::DELIVERY_TYPE_BOTH:
                $body['email'] = $user_settings['email'];
                $body['smsNumber'] = $user_settings['smsNumber'];
                $body['deliveryType'] = SBRS_TFA_Auth::DELIVERY_TYPE_BOTH;
                break;
            default:
                $body['email'] = $user->user_email;
                $body['deliveryType'] = SBRS_TFA_Auth::DELIVERY_TYPE_EMAIL;
                break;
        }

        $body['email'] = !empty($body['email']) ? $body['email'] : $user->user_email;

        $url = $this->get_server_api_url().'/two-factor-dispatch';

        $res = $this->wp->wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'sslverify' => false,
            'body' => $body,
            'cookies' => array()
        ));

        if (!$this->wp->is_wp_error($res)) {
            if (isset($res['body'])) {
                $body = json_decode($res['body']);

                if (isset($body->token) && $body->token != '') {
                    $keyphrase = $body->token;
                }

                if (isset($body->expiry) && $body->expiry != '') {
                    $expiry = $body->expiry;
                }
            }
        }

        if (!isset($keyphrase) || $keyphrase == '') {
            $this->logger->log('error', 'TFA Authenticate', 'Failed sending keyphrase to gateway', array('res' => var_export($res, true)));
            if ($user->data->user_email != '') {
                // Fallback
                $keyphrase = mt_rand(10000, 99999);

                if (!isset($expiry)) {
                    $expiry = 120;
                }
                // Send keyphrase
                if (!$this->wp->wp_mail($user->data->user_email, 'TFA Keyphrase', 'Hi, Your Keyphrase is ' . $keyphrase)) {
                    $this->logger->log('error', 'TFA Authenticate', 'Failed sending keyphrase with php mailer.', array('mailErr' => $this->wp->get_mail_err()));
                    $keyphrase = '';
                }
            }
        }

        if (isset($keyphrase) && $keyphrase != '' && isset($expiry) && is_numeric($expiry)) {
            $auth_tfa = array(
                'auth_key' => $keyphrase,
                'expiry' => $this->wp->current_time('timestamp', 1) + $expiry * 60,
                'cred' => array(
                    'user_login' => $user->user_login,
                    'remember' => true
                )
            );

            $auth_tfa = json_encode($auth_tfa);

            $this->wp->update_sabres_user_meta($user->ID, 'auth_tfa', $auth_tfa);

            return true;
        }

        $this->plugin_settings->mod_tfa_active = 'False';

        return false;
    }

    private function validate_tfa_cookie($tfa_device_id) {
        return strlen($tfa_device_id) <= 40 && !preg_match('/[^a-z0-9\s]/i',$tfa_device_id);
    }

    private function set_tfa_device_cookie($user_login,$user) {
        $tfa_cookie_name = $this->get_tfa_cookie_name($user_login);
        $tfa_device_id = null;

        if (isset($_COOKIE[$tfa_cookie_name])) {
            $tfa_device_id = $_COOKIE[$tfa_cookie_name];
            if (!$this->validate_tfa_cookie($tfa_device_id)) { //allow only alpha numeric charecters or whitspace
                $tfa_device_id = null;
            }
        }
        if ($tfa_device_id == null) {
            $tfa_device_id = hash_hmac( 'sha1', time(), 'XkkxRUYB' );
            setcookie($tfa_cookie_name,$tfa_device_id,time()+(10*365*24*60*60),'','',false,true);
        }
        $devices = $this->wp->get_sabres_user_meta( $user->ID, self::DEVICE_META_KEY, true);
        $devices = json_decode( $devices, true );

        if ($devices == null) {
            $devices = array();
        }

        $devices[$tfa_device_id]=array('lastLogin'=>gmdate('Y-m-d H:i:s'));

        $this->wp->update_sabres_user_meta( $user->ID, self::DEVICE_META_KEY, SBRS_Helper_Utils::get_json( $devices ) );

    }

    private function get_tfa_cookie_name($user_login) {
        return 'sabres_tfa_'.urlencode($user_login);

    }


}
