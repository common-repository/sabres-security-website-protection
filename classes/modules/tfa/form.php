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

require_once( __DIR__ . '/../../wp.php' );

class SBRS_TFA_Form {
    /** @var  SBRS_WP */
    private $wp;

    public function __construct($wp) {
        $this->wp = $wp;
    }

    public function show( $user ) {
        ?>

        <input type="hidden" name="wp-auth-id"    id="wp-auth-id"    value="<?php echo $this->wp->esc_attr( $user->ID ); ?>" />
        <input type="hidden" name="username" value="<?php echo $this->wp->esc_attr( $user->user_login ); ?>" />
        <input type="hidden" name="pwd" value="password"/>
        <p>
            <label for="sabres_tfa_keyphrase"><?php $this->wp->_e( 'Keyphrase' ) ?><br />
                <input type="password" name="sabres_tfa_keyphrase" id="sabres_tfa_keyphrase" class="input" size="20" /></label>
        </p>
        <?php
    }
}