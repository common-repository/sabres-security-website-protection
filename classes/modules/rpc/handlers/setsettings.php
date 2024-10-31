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
require_once( __DIR__ . '/../../../helpers/fail.php' );

class SBRS_RPC_Set_Settings
{
    /** @var  SBRS_Settings */
    private $settings;
    public function __construct($settings) {
      $this->settings=$settings;
    }
    public function execute($rpc_data)
    {
        if (empty($rpc_data['settings'])) {
            SBRS_Helper_Fail::byeArr(array('message' => "RPC: set_settings missing settings parameter",
                'code' => 400
            ));
        }

        $res = $this->save(urldecode($rpc_data['settings']));
        echo $res;

    }

    public function save($settings = null)
    {
        parse_str($settings, $insettings);
        if (empty($insettings))
            SBRS_Helper_Fail::byeArr(array('message' => "RPC: set_settings can not load settings in " . var_export($settings, true),
                'code' => 500
            ));
        return $this->settings->set_values($insettings);
    }
}
