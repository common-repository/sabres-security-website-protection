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

require_once( __DIR__ . '/../../../settings.php' );

class SBRS_RPC_Get_Settings {

    /** @var  SBRS_Settings */
    private $settings;
    public function __construct($settings) {
      $this->settings=$settings;
    }


    public function execute($rpc_data) {

      $fields = null;

      if ( !empty( $rpc_data['fields'] ) ) {
          $fields = explode( ',', $rpc_data['fields'] );
      }
      $settings=$this->settings;
      $res = $settings->get_json( $fields );
      echo $res;
    }

}
