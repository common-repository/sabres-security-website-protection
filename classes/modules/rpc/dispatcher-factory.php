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

require_once( __DIR__ . '/callback-parser.php' );
require_once( __DIR__ . '/dispatcher.php' );
require_once( __DIR__ . '/handler-factory.php' );
require_once( __DIR__ . '/message-content-provider.php' );
require_once( __DIR__ . '/../../helpers/fail.php' );

class SBRS_RPC_Dispatcher_Factory
{
    private $wp;
    private $settings;
    private $factory;
    private $scanner;
    private $logger;
    private $inventory_factory;
    private $ext_url_provider;

    public function __construct($ext_url_provider,$wp, $settings, $factory, $scanner, $logger, $inventory_factory)
    {
        $this->ext_url_provider=$ext_url_provider;
        $this->wp = $wp;
        $this->settings = $settings;
        $this->factory = $factory;
        $this->scanner = $scanner;
        $this->logger = $logger;
        $this->inventory_factory = $inventory_factory;
    }

    public function create($type)
    {
        switch ($type) {
            case'dispatcher':
                return $this->get_dispatcher();
            default:
                return $this->get_dispatcher();
        }
    }

    public function get_server_api_url()
    {
        if ($this->settings->https == '' || strcasecmp($this->settings->https, 'true') == 0) {
            return $this->ext_url_provider->get_base_sagw_url();
        } else {
            return $this->ext_url_provider->get_base_sagw_url_plain();
        }
    }

    private function get_dispatcher()
    {
        $argsParserCallback = new SBRS_RPC_Callback_Parser();
        $postUrl=$this->get_server_api_url().'/round-trip-wppp';
        $messageContentProvider = new SBRS_RPC_Message_Content_Provider($this->wp,$postUrl);
        $factoryHandler = new SBRS_RPC_Handler_Factory($this->wp, $this->settings, $this->factory, $this->scanner, $this->logger, $this->inventory_factory);
        return new SBRS_RPC_Dispatcher($this->wp, $this->settings, $argsParserCallback, $messageContentProvider, $factoryHandler);
    }

}
