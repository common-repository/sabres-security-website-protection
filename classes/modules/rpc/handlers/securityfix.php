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

require_once( __DIR__ . '/../../security/scanner.php' );
require_once( __DIR__ . '/../../../settings.php' );

class SBRS_RPC_Security_Fix
{

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_Scanner */
    private $scanner;

    public function __construct(SBRS_Settings $settings, SBRS_Scanner $scanner)
    {
        $this->settings = $settings;
        $this->scanner = $scanner;
    }

    public function execute($rpc_data)
    {
        $this->scanner->init($this->settings->get_settings('mod_scanner'), 'security');
        $res = json_encode($this->scanner->fix_issue($rpc_data['code'], $rpc_data['uniqueId']));
        echo $res;
    }

}
