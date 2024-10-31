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

require_once( __DIR__ . '/../../../wp.php' );

class SBRS_RPC_Is_Plugin_Active {

    /** @var  SBRS_WP */
    private $wp;

    public function __construct(SBRS_WP $wp)
    {
        $this->wp = $wp;
    }

    public function execute($rpc_data) {
        $result = $this->wp->is_plugin_active(SABRES_PLUGIN_BASE_NAME);
        //echo var_export($result,true);
        echo $result;
    }

}
