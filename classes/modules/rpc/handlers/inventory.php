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

require_once( __DIR__ . '/../../inventory/base.php' );

class SBRS_RPC_Inventory
{
    /** @var  SBRS_Inventory_Base */
    private $inventory;

    public function __construct($inventory)
    {
        $this->inventory = $inventory;
    }

    public function execute($rpc_data)
    {
        $files = null;
        $exts = null;

        if (!empty($rpc_data['files'])) {
            $files = $rpc_data['files'];
        }

        if (!empty ($rpc_data['exts'])) {
            $raw_exts = array();
            foreach (explode(';', $rpc_data['exts']) as $ext) {
                array_push($raw_exts, $ext);
            }

            $exts = count($raw_exts) ? $raw_exts : $exts;
        }

        echo json_encode($this->inventory->get_inventory($files, $exts));
    }

}
