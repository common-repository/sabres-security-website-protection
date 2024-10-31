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

final class SBRS_Firewall_CheckPOST {

    public function check_request($unique_id,$settings,$server) {
      // Server offline
      if ( strcasecmp( $settings->server_offline, 'true' ) == 0 ) return;
      // Check tokens
      if ( $settings->websiteSabresServerToken === '' ) return;

      $data=array();
      $data['websitesabresservertoken']=$settings->websiteSabresServerToken;
      $data['uniqueid']=$unique_id;
      $this->setIfExists($data,'HTTP_CLIENT_IP');
      $this->setIfExists($data,'HTTP_X_FORWARDED_FOR');
      $this->setIfExists($data,'HTTP_X_FORWARDED');
      $this->setIfExists($data,'HTTP_FORWARDED_FOR');
      $this->setIfExists($data,'HTTP_FORWARDED');
      $this->setIfExists($data,'REMOTE_ADDR');
      $this->setIfExists($data,'SERVER_ADDR');
      $this->setIfExists($data,'REQUEST_URI');
      $this->setIfExists($data,'HTTP_USER_AGENT');
      $res=$server->call( 'validate-post-request', '', $data,array('timeout'=>30));
      if (isset($res) && is_array($res) && isset($res['response']) && isset($res['response']['code']) && isset($res['response']['code'])==200) {
        $response=json_decode($res['body'],true);
        return $response;
      }

    }

    private function setIfExists(&$data,$key) {
      $value=@$_SERVER[$key];
      if (isset($value))
        $data[$key]=$value;
    }


}
