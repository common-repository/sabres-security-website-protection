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

require_once( __DIR__ . '/../../firewall/engine.php' );

class SBRS_RPC_Firewall {
    /** @var  SBRS_Firewall_Engine */
    private $firewall;

    public function __construct($firewall)
    {
        $this->firewall = $firewall;
    }

    public function execute($rpc_data) {

        $topic = null;
        $data = null;
        $purge = null;

        if ( !empty( $rpc_data['topic'] ) ) {
            $topic = $rpc_data['topic'];
        }
        if ( !empty( $rpc_data['body'] ) ) {
            $data = urldecode( $rpc_data['body'] );
        }
        if ( !empty( $rpc_data['purge'] ) ) {
            $purge = $rpc_data['purge'];
        }


        if ( !empty( $topic ) && !empty( $data ) ) {
            //TODO move this methods in separate class
            switch ( $topic ) {
                case 'countries':
                    $this->firewall->add_countries( json_decode( $data, true ) );
                    break;
                case 'cookies':
                    $this->firewall->add_unique_ids( json_decode( $data, true ) );
                    break;
                case 'custom':
                    $this->firewall->add_custom_range( json_decode( $data, true ), $purge );
                    break;
            }
        }
    }

}
