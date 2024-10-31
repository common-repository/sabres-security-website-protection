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
require_once( __DIR__ . '/../../wp.php' );

class SBRS_TFA_Settings
{
    const DELIVERY_TYPE_EMAIL = 'email';
    const DELIVERY_TYPE_SMS = 'sms';
    const DELIVERY_TYPE_BOTH = 'both';

    const USER_META_KEY = 'tfa';

    const PHONE_PATTERN = '/^\+?[0-9]{7,}$/';

    const STRICTNESS_TYPE_NEW_DEVICE='new-device';
    const STRICTNESS_TYPE_EVERY_LOGIN='every-login';

    /** @var SBRS_WP  */
    private $wp;
    /**
     * Settings constructor.
     * @param SBRS_WP $wp
     */
    public function __construct(SBRS_WP $wp) {
        $this->wp = $wp;
    }

    public function get_settings($user) {
        $settings = $this->wp->get_sabres_user_meta( $user->ID, self::USER_META_KEY, true);
        $settings = json_decode( $settings, true );

        $settings = is_null($settings) ? array() : $settings;

        return array_merge($this->get_default_settings( $user ), $settings);
    }

    public function get_default_settings(\WP_User $user) {
        return array(
            'delivery' => SBRS_TFA_Auth::DELIVERY_TYPE_EMAIL,
            'email' => $user->user_email,
            'strictness' => SBRS_TFA_Auth::STRICTNESS_TYPE_NEW_DEVICE,
            'device-expiry-checked' => false,
            'device-expiry-days' => 2
        );
    }

    public function update_settings(WP_User $user, $settings) {

        $errors = $this->validate( $settings );

        if( count( $errors ) == 0) {

            if( $settings['delivery'] == static::DELIVERY_TYPE_EMAIL ) {
                $settings['smsNumber'] = '';
            }

            if( $settings['delivery'] == static::DELIVERY_TYPE_SMS ) {
                $settings['email'] = '';
            }

            $this->wp->update_sabres_user_meta( $user->ID, static::USER_META_KEY, json_encode( $settings ) );
        }

        return $errors;
    }

    protected function validate(&$settings) {
        $errors = array();

        $delivery_types = array(
            static::DELIVERY_TYPE_EMAIL,
            static::DELIVERY_TYPE_SMS,
            static::DELIVERY_TYPE_BOTH
        );


        if( !array_key_exists( 'delivery', $settings ) || !in_array( $settings['delivery'], $delivery_types ) ) {
            $errors[] = "Invalid token delivery";
        }

        $strictness_types=array(static::STRICTNESS_TYPE_NEW_DEVICE,static::STRICTNESS_TYPE_EVERY_LOGIN);

        if( !array_key_exists( 'strictness', $settings ) || !in_array( $settings['strictness'], $strictness_types ) ) {
            $errors[] = "Invalid token strictness";
        }

        if( $settings['delivery'] == static::DELIVERY_TYPE_BOTH || $settings['delivery'] == static::DELIVERY_TYPE_EMAIL) {
            if( !array_key_exists( 'email', $settings ) || !is_email( $settings['email'] ) ) {
                $errors[] = "Invalid email";
            }
        }

        if( $settings['delivery'] == static::DELIVERY_TYPE_BOTH || $settings['delivery'] == static::DELIVERY_TYPE_SMS) {

            $number_str = isset( $settings['smsNumber'] ) ? $settings['smsNumber'] : '';
            if( !preg_match( static::PHONE_PATTERN , $number_str ) ) {
                $errors[] = "Invalid phone number";
            }

        }

        if ( ! isset($settings['device-expiry-checked']) )
            $errors[] = "Device expiry checked can not be empty";
        else if ( ! $settings['device-expiry-checked'] )
            $settings['device-expiry-checked'] = false;
        else
            $settings['device-expiry-checked'] = true;

        if ( ! isset($settings['device-expiry-days']) )
            $errors[] = "Device expiry days can not be empty";
        else if ($settings['device-expiry-days']<1 || $settings['device-expiry-days']>1000)
            $errors[] = "Invalid value for device expiry days. Value should be between 1 and 1000";

        return $errors;
    }

    public function reset($user)
    {
        $default = $this->get_default_settings($user);
        $this->update_settings($user, $default);

        return $default;
    }
}