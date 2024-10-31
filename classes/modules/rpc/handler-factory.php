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
require_once( __DIR__ . '/ifactory-handler.php' );
require_once( __DIR__ . '/message-content-provider.php' );
require_once( __DIR__ . '/handlers/activate.php' );
require_once( __DIR__ . '/handlers/dir_it.php' );
require_once( __DIR__ . '/handlers/getlog.php' );
require_once( __DIR__ . '/handlers/clearlog.php' );
require_once( __DIR__ . '/handlers/firewall.php' );
require_once( __DIR__ . '/handlers/getsettings.php' );
require_once( __DIR__ . '/handlers/getversion.php' );
require_once( __DIR__ . '/handlers/inventory.php' );
require_once( __DIR__ . '/handlers/securityfix.php' );
require_once( __DIR__ . '/handlers/securityscan.php' );
require_once( __DIR__ . '/handlers/setsettings.php' );
require_once( __DIR__ . '/handlers/wp-is-active.php' );
require_once( __DIR__ . '/handlers/is-authorized-ip.php' );
require_once( __DIR__ . '/../firewall/engine-factory.php' );
require_once( __DIR__ . '/../inventory/factory.php' );
require_once( __DIR__ . '/../security/scanner.php' );
require_once( __DIR__ . '/../../logger.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );
require_once( __DIR__ . '/../../helpers/fail.php' );

class SBRS_RPC_Handler_Factory implements SBRS_RPC_IFactory_Handler
{
    private $settings;
    private $wp;
    /** @var  SBRS_Firewall_Engine_Factory */
    private $factory;
    /** @var  SBRS_Inventory_Factory */
    private $inventory_factory;
    private $scanner;
    private $logger;

    public function __construct(SBRS_WP $wp, SBRS_Settings $settings, $engineFactory, SBRS_Scanner $scanner, SBRS_Logger $logger, SBRS_Inventory_Factory $inventory_factory)
    {
        $this->settings = $settings;
        $this->wp = $wp;
        $this->factory = $engineFactory;
        $this->scanner = $scanner;
        $this->logger = $logger;
        $this->inventory_factory = $inventory_factory;
    }

    public function createHandler($op)
    {
        switch ($op) {
            case 'activate':
                return new SBRS_RPC_Activate($this->settings);
                break;
            case 'dirIt':
                return new SBRS_RPC_Dir_It();
                break;
            case 'getSettings':
                return new SBRS_RPC_Get_Settings($this->settings);
                break;
            case 'getVersion':
                return new SBRS_RPC_Get_Version();
                break;
            case 'firewallSettings':
                return new SBRS_RPC_Firewall($this->factory->create('engine'));
                break;
            case 'settings':
                return new SBRS_RPC_Set_Settings($this->settings);
                break;
            case 'performSecurityScan':
                return new SBRS_RPC_Security_Scan($this->settings, $this->scanner);
                break;
            case 'performSecurityFix':
                return new SBRS_RPC_Security_Fix($this->settings, $this->scanner);
                break;
            case 'getLog':
                return new SBRS_RPC_Get_Log($this->logger);
                break;
            case 'clearLog':
                return new SBRS_RPC_Clear_Log($this->logger);
                break;
            case 'coreInventory':
                return new SBRS_RPC_Inventory($this->inventory_factory->create('core'));
                break;
            case 'pluginsInventory':
                return new SBRS_RPC_Inventory($this->inventory_factory->create('plugins'));
                break;
            case 'themesInventory':
                return new SBRS_RPC_Inventory($this->inventory_factory->create('themes'));
                break;
            case 'themesInventory2':
                return new SBRS_RPC_Inventory($this->inventory_factory->create('themes2'));
                break;
            case 'wpIsActive':
                return new SBRS_RPC_Is_Plugin_Active($this->wp);
                break;
            case 'isAuthorizedIP':
                return new SBRS_RPC_Is_Authorized_IP($this->settings);
                break;
            default:
                SBRS_Helper_Fail::bye("Failed to create Handler");
        }
    }
}
