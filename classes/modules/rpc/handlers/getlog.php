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

require_once( __DIR__ . '/../../../logger.php' );

class SBRS_RPC_Get_Log
{

    /** @var SBRS_Logger */
    private $logger;

    public function __construct(SBRS_Logger $logger)
    {
        $this->logger = $logger;
    }

    public function execute($rpc_data)
    {
        $start = null;
        $end = null;

        if (!empty($rpc_data['start']) && !empty($rpc_data['end'])) {
            $start = $rpc_data['start'];
            $end = $rpc_data['end'];
        }
//      $entries = $this->logger->get_entries( $start, $end );
        $entries = $this->logger->get_entries_new($rpc_data);
        $res = json_encode(array_values($entries));
        echo $res;
    }

}
