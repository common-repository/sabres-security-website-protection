<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once( __DIR__ . '/admin.php');
require_once( __DIR__ . '/checker.php');
require_once( __DIR__ . '/event-manager.php');
require_once( __DIR__ . '/logger.php');
require_once( __DIR__ . '/plugin.php');
require_once( __DIR__ . '/request.php');
require_once( __DIR__ . '/settings.php');
require_once( __DIR__ . '/traffic-dispatcher.php');
require_once( __DIR__ . '/wp.php');
require_once( __DIR__ . '/helpers/server.php');
require_once( __DIR__ . '/helpers/utils.php');
require_once( __DIR__ . '/modules/activation.php');
require_once( __DIR__ . '/modules/cron.php');
require_once( __DIR__ . '/modules/firewall.php');
require_once( __DIR__ . '/modules/lifecycle.php');
require_once( __DIR__ . '/modules/rpc.php');
require_once( __DIR__ . '/modules/tfa.php');
require_once( __DIR__ . '/modules/traffic.php');
require_once( __DIR__ . '/modules/security.php');
require_once( __DIR__ . '/modules/firewall/engine-factory.php');
require_once( __DIR__ . '/modules/inventory/factory.php');
require_once( __DIR__ . '/modules/inventory/files.php');
require_once( __DIR__ . '/modules/lifecycle/gateway.php');
require_once( __DIR__ . '/modules/rpc/dispatcher-factory.php');
require_once( __DIR__ . '/modules/security/scanner.php');
require_once( __DIR__ . '/modules/security/scanner-engine-factory.php');
require_once( __DIR__ . '/modules/tfa/implementation.php');
require_once( __DIR__ . '/modules/auto_update.php');
require_once( __DIR__ . '/helpers/ext-url/ext-url-provider.php');

class SBRS_DI
{

    private static $wp;
    private static $plugin;
    private static $settings;
    private static $rpc;
    private static $activation;
    private static $server;
    private static $traffic;
    private static $firewall;
    private static $dispatcher_factory;
    private static $engine_factory;
    private static $scanner;
    private static $scanner_factory;
    private static $request;
    private static $traffic_dispatcher;
    private static $security;
    private static $logger;
    private static $cron;
    private static $tfa;
    private static $tfa_impl;
    private static $event_manager;
    private static $checker;
    private static $lifecycle;
    private static $inventory_factory;
    private static $inventory_files;
    private static $admin;
    private static $ext_url_provider;
    private static $auto_update;


    private $plugin_base;

    public function __construct($plugin_base)
    {
        $this->plugin_base = $plugin_base;
    }

    public function init_singletons()
    {
        self::$wp = new SBRS_WP();
        self::$event_manager = new SBRS_Event_Manager();

        self::$logger = new SBRS_Logger($this->get_wp());
        self::$checker = new SBRS_Checker();
        self::$settings = new SBRS_Settings($this->get_wp(), $this->get_logger());

        if (strcasecmp($this->get_settings()->debug, 'true')!==0)
          self::$ext_url_provider=new SBRS_External_URL_Provider();
        else {
          require_once( __DIR__ . '/helpers/ext-url/dev-url-provider.php');
          self::$ext_url_provider=new SBRS_Dev_URL_Provider();
        }

        self::$request = new SBRS_Request($this->get_settings(), 'b827v2b9nw893s');

        self::$auto_update = new SBRS_Auto_Update($this->plugin_base,$this->get_wp(),$this->get_request(),$this->get_ext_url_provider(),$this->get_logger());

        self::$server = new SBRS_Helper_Server($this->get_ext_url_provider(),$this->get_wp(), $this->get_settings(), $this->get_logger());


        self::$traffic_dispatcher = new SBRS_Traffic_Dispatcher($this->get_ext_url_provider(),$this->get_request(), $this->get_server(), $this->get_settings(), $this->get_wp(), $this->get_logger());

        self::$scanner_factory = new SBRS_Scanner_Engine_Factory($this->get_settings(), $this->get_wp());
        self::$scanner = new SBRS_Scanner($this->get_server(), $this->get_scanner_factory());
        self::$inventory_files = new SBRS_Inventory_Files($this->get_wp());
        self::$inventory_factory = new SBRS_Inventory_Factory($this->get_wp(), $this->get_logger(), $this->get_inventory_files());
        self::$engine_factory = new SBRS_Firewall_Engine_Factory($this->get_settings(), $this->get_wp(), $this->get_request(), $this->get_traffic_dispatcher(), $this->get_server());

        self::$dispatcher_factory = new SBRS_RPC_Dispatcher_Factory($this->get_ext_url_provider(),$this->get_wp(), $this->get_settings(), $this->get_engine_factory(), $this->get_scanner(), $this->get_logger(), $this->get_inventory_factory());

        self::$security = new SBRS_Security();

        self::$tfa_impl = new SBRS_TFA_Implementation($this->get_ext_url_provider(),$this->get_wp(), $this->get_settings(), $this->get_logger(), $this->get_request());

        self::$rpc = new SBRS_RPC($this->get_wp(), $this->get_dispatcher_factory(), $this->get_request(), $this->get_settings(), $this->get_logger(), $this->get_server());
        self::$activation = new SBRS_Activation($this->plugin_base, $this->get_wp(), $this->get_settings(), $this->get_server(), $this->get_request(), $this->get_logger());
        self::$traffic = new SBRS_Traffic($this->get_wp(), $this->get_settings(), $this->get_request(), $this->get_traffic_dispatcher(),$this->get_logger());
        self::$firewall = new SBRS_Firewall($this->get_wp(), $this->get_settings(), $this->get_engine_factory());
        self::$cron = new SBRS_Cron($this->get_wp(), $this->get_settings(), $this->get_server(), $this->get_logger(), $this->get_rpc());

        self::$tfa = new SBRS_Tfa($this->get_wp(), $this->get_settings(), $this->get_tfa_implementation());
        self::$lifecycle = new SBRS_Lifecycle($this->get_wp(), $this->get_lifecycle_handlers());
        self::$admin = new SBRS_Admin($this->get_ext_url_provider(),$this->get_settings(), $this->get_wp(), $this->get_logger(), $this->get_activation(), $this->get_tfa_implementation());


        self::$plugin = new SBRS_Plugin(
            $this->plugin_base,
            $this->get_wp(),
            $this->get_settings(),
            $this->get_event_manager(),
            $this->get_rpc(),
            $this->get_activation(),
            $this->get_traffic(),
            $this->get_firewall(),
            $this->get_security(),
            $this->get_cron(),
            $this->get_tfa(),
            $this->get_lifecycle(),
            $this->get_checker(),
            $this->get_admin(),
            $this->get_auto_update()
        );

    }

    public function get_wp()
    {
        return self::$wp;
    }

    public function get_plugin()
    {
        return self::$plugin;
    }

    public function get_settings()
    {
        return self::$settings;
    }

    public function get_request()
    {
        return self::$request;
    }

    public function get_server()
    {
        return self::$server;
    }

    public function get_rpc()
    {
        return self::$rpc;
    }

    public function get_traffic_dispatcher()
    {
        return self::$traffic_dispatcher;
    }

    public function get_activation()
    {
        return self::$activation;
    }

    public function get_firewall()
    {
        return self::$firewall;
    }

    public function get_traffic()
    {
        return self::$traffic;
    }

    public function get_dispatcher_factory()
    {
        return self::$dispatcher_factory;
    }

    public function get_engine_factory()
    {
        return self::$engine_factory;
    }

    public function get_scanner()
    {
        return self::$scanner;
    }

    public function get_scanner_factory()
    {
        return self::$scanner_factory;
    }

    public function get_security()
    {
        return self::$security;
    }

    public function get_logger()
    {
        return self::$logger;
    }

    public function get_cron()
    {
        return self::$cron;
    }

    public function get_tfa()
    {
        return self::$tfa;
    }

    public function get_tfa_implementation()
    {
        return self::$tfa_impl;
    }

    public function get_event_manager()
    {
        return self::$event_manager;
    }

    public function get_lifecycle()
    {
        return self::$lifecycle;
    }

    public function get_checker()
    {
        return self::$checker;
    }

    public function get_inventory_factory()
    {
        return self::$inventory_factory;
    }

    public function get_inventory_files()
    {
        return self::$inventory_files;
    }

    public function get_lifecycle_handlers()
    {
        return array(
            new SBRS_Lifecycle_Gateway(self::$server, self::$request, self::$settings, self::$wp),
            //new WriteLog(self::$request, self::$settings, self::$wp, self::$logger),
        );
    }

    public function get_admin()
    {
        return self::$admin;
    }

    public function get_ext_url_provider() {
      return self::$ext_url_provider;
    }

    public function get_auto_update() {
      return self::$auto_update;
    }
}
