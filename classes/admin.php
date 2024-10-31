<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once( __DIR__ . '/logger.php' );
require_once( __DIR__ . '/module.php' );
require_once( __DIR__ . '/modules/tfa/settings.php' );
require_once( __DIR__ . '/settings.php' );
require_once( __DIR__ . '/helpers/ext-url/ext-url-provider.php');

class SBRS_Admin extends SBRS_Module
{

    /** @var  SBRS_Logger */
    private $logger;

    /** @var  SBRS_Settings */
    private $settings;

    /** @var  SBRS_TFA_Settings */
    private $tfa_settings;

    /** @var  SBRS_WP */
    private $wp;

    /** @var  SBRS_Activation */
    private $activation;

    private $ext_url_provider;

    public function __construct(  $ext_url_provider , SBRS_Settings $settings, SBRS_WP $wp, SBRS_Logger $logger, SBRS_Activation $activation, SBRS_TFA_Implementation $tfa  )
    {
        $this->settings = $settings;
        $this->wp = $wp;
        $this->logger = $logger;
        $this->ext_url_provider = $ext_url_provider;
        $this->activation = $activation;
        $this->tfa_settings = $tfa->get_tfa_settings();
    }

    public function is_enabled()
    {
        return true;
    }

    public function run( SBRS_Event_Manager $manager )
    {
        if ($this->is_enabled()) {
            $this->register_hook_callbacks();
            $this->register_events( $manager );
        }
    }

    public function register_events( SBRS_Event_Manager $manager )
    {
    }

    public function activate()
    {

    }

    public function deactivate()
    {

    }

    public function register_hook_callbacks()
    {
        // Register the admin area menu-building functions.
        $this->wp->add_action('admin_menu', array($this, 'admin_menu'));

        // Register the admin area notices function.
        $this->wp->add_action((is_multisite() ? 'network_' : '') . 'admin_notices', array($this, 'admin_notices'));

        // Register functions to handle the AJAX requests.
        $this->wp->add_action('wp_ajax_tour_finished', array($this, 'tour_finished'));
        $this->wp->add_action('wp_ajax_update_features', array($this, 'update_features'));
        $this->wp->add_action('wp_ajax_load_features', array($this, 'load_features'));
        $this->wp->add_action('wp_ajax_get_default_features', array($this, 'get_default_features'));
        $this->wp->add_action('wp_ajax_reset_activation', array($this, 'reset_activation'));
        $this->wp->add_action('wp_ajax_reset_settings', array($this, 'reset_settings'));
        $this->wp->add_action('wp_ajax_save_email', array($this, 'save_email'));
        $this->wp->add_action('wp_ajax_submit_verification', array($this, 'submit_verification'));
        $this->wp->add_action('wp_ajax_is_sso_email', array($this, 'is_sso_email'));

        // Register the admin area settings functions.
        $this->wp->add_action('admin_post_submit_settings', array($this, 'submit_settings'));
        $this->wp->add_action('admin_post_cancel_settings', array($this, 'cancel_settings'));
    }

    public function admin_menu()
    {
        $this->wp->add_menu_page(
            'Sabres dashboard',
            'Sabres',
            'activate_plugins',
            'sabres',
            array($this, 'show_dashboard'),
                plugins_url('images/logo_dark_16x16.png',__DIR__)
        );

        $this->wp->add_options_page(
            'Sabres',
            'Sabres',
            'activate_plugins',
            'sabres',
            array($this, 'show_dashboard')
        );

        /* if ( !$this->settings->should_trigger_activation() ) {

            $this->wp->add_submenu_page(
                'sabres',
                'Sabres dashboard',
                'Dashboard',
                'activate_plugins',
                'sabres',
                    array($this, 'show_dashboard')
            );

            $this->wp->add_submenu_page(
                'sabres',
                'Sabres settings',
                'Settings',
                'activate_plugins',
                'sabres_settings',
                    array($this, 'show_settings')
            );

            $this->wp->add_submenu_page(
                'sabres',
                'Sabres issues',
                'Issues',
                'activate_plugins',
                'sabres_issues',
                    array($this, 'show_issues')
            );

            $this->wp->add_submenu_page(
                'sabres',
                'Sabres vulnerabilities',
                'Vulnerabilities',
                'activate_plugins',
                'sabres_vulnerabilities',
                    array($this, 'show_vulnerabilities')
            );

            $this->wp->add_submenu_page(
                'sabres',
                'Sabres reports',
                'Reports',
                'activate_plugins',
                'sabres_reports',
                    array($this, 'show_reports')
            );

            $this->wp->add_submenu_page(
                'sabres',
                'Sabres glossary',
                'Glossary',
                'activate_plugins',
                'sabres_glossary',
                    array($this, 'show_glossary')
            );
        } */
    }

    public function admin_notices()
    {
        global $current_screen;
        global $pagenow;

        // Use global var or get current screen (since WP 3.1)
        if (empty($current_screen) && function_exists('get_current_screen')) {
            $current_screen = get_current_screen();
        }

        // Enqueue notices on selected pages only (Dashboard and Plugins)
        $current_screen_id = isset($current_screen->id) ? $current_screen->id : substr($pagenow, 0, -4);
        if ($current_screen_id !== 'index' && $current_screen_id !== 'dashboard' && $current_screen_id !== 'plugins') {
            return null;
        }

        // Do not display a notice...
        if ($this->settings->sso_email || //..if user entered an email
                ($this->settings->ts_first_noticed && time() - $this->settings->ts_first_noticed > 6 * 30.5 * 86400) || //..or user isn't interested six months or more
                ($this->settings->ts_last_noticed && time() - $this->settings->ts_last_noticed < 86400) || //..or pass less than a day between consecutive notices
                ($this->settings->ts_last_visited && time() - $this->settings->ts_last_visited < 7 * 86400) //..or user hasn't visited plug-in less than a week
        ) {
            return null;
        }

        // Update the first and last notices timestamps
        if (!$this->settings->ts_first_noticed) {
            $this->settings->ts_first_noticed = time();
        }
        $this->settings->ts_last_noticed = time();

        // Add a dismissible notice
        $sabres_plugin_url = 'admin.php?page=sabres#sabres-tour';
        $network_admin_url = function_exists('network_admin_url') ?
                network_admin_url($sabres_plugin_url) : $this->wp->admin_url($sabres_plugin_url);
        ?>
        <div class="notice notice-info is-dismissible">
            <p style="font-size:16px">
                To make your website as secure as possible, take a moment to complete registration in Sabres:
                &nbsp;<a class="button button-primary pull-right" href="<?php echo esc_url($network_admin_url); ?>">Register</a>
                <br>
                <em style="font-size:smaller">Once you register you will receive a confirmation E-mail with a verification code.</em>
            </p>
        </div>
        <?php
    }

    public function show_dashboard()
    {
//        if ( $this->settings->should_trigger_activation() && ! $this->settings->first_activation ) {
       if ( $this->settings->should_trigger_activation()) {
            $this->show_activation_failed();
        } else {
            require_once SABRES_PATH . '/views/main.php';
        }

        // Update the last visit's timestamp
        $this->settings->ts_last_visited = time();
    }

    public function show_settings()
    {
        if ($this->settings->should_trigger_activation()) {
            $this->show_activation_failed();
        } else {
            require_once SABRES_PATH . '/views/settings.php';
        }
    }

    public function show_issues()
    {
        if ($this->settings->should_trigger_activation()) {
            $this->show_activation_failed();
        } else {
            require_once SABRES_PATH . '/views/issues.php';
        }
    }

    public function show_vulnerabilities()
    {
        if ($this->settings->should_trigger_activation()) {
            $this->show_activation_failed();
        } else {
            require_once SABRES_PATH . '/views/vulnerabilities.php';
        }
    }

    public function show_reports()
    {
        if ($this->settings->should_trigger_activation()) {
            $this->show_activation_failed();
        } else {
            require_once SABRES_PATH . '/views/reports.php';
        }
    }

    public function show_glossary()
    {
        if ($this->settings->should_trigger_activation()) {
            $this->show_activation_failed();
        } else {
            require_once SABRES_PATH . '/views/glossary.php';
        }
    }

    public function show_activation_failed()
    {
        require_once SABRES_PATH . '/views/activation-failed.php';
    }

    private function write_activation_data()
    {
        $gateway_connection = strcasecmp($this->settings->server_offline,'true')!==0;
        $service_activated = ! $this->settings->should_trigger_activation();
        $traffic_monitor = strcasecmp($this->settings->isActive,true)!==0;
        $is_activating = (false !== $this->wp->get_transient('sabres_activation_lock'));

        $lastError = $this->logger->get_last_activation_error();

        echo <<<EOL
<script type="text/javascript">
jQuery(function ($) {
    $.sabres=$.sabres || {};
    $.sabres.act_fail=$.sabres.act_fail || {};
    $.sabres.act_fail.data = {
        gatewayConnection:"$gateway_connection",
        serviceActivated:"$service_activated",
        trafficMonitor:"$traffic_monitor",
        isActivating:"$is_activating",
        lastError:"$lastError"
    };
  });
</script>
EOL;
    }

    public function tour_finished()
    {
        $this->settings->first_activation = 'False';

        $this->wp->wp_real_die();
    }

    public function update_features()
    {
        if (isset($_POST['firewall']))
            $this->settings->mod_firewall_active = $_POST['firewall'] == "true" ? "true" : "false";
        if ( isset( $_POST['firewall_fake-crawler'] ) )
            $this->settings->mod_firewall_fake_crawler = $_POST['firewall_fake-crawler'] == "true" ? "true" : "false";
        if ( isset( $_POST['firewall_known-attacks'] ) )
            $this->settings->mod_firewall_known_attacks = $_POST['firewall_known-attacks'] == "true" ? "true" : "false";
        if ( isset( $_POST['firewall_anon-browsing'] ) )
            $this->settings->mod_firewall_anon_browsing = $_POST['firewall_anon-browsing'] == "true" ? "true" : "false";
        if ( isset( $_POST['firewall_waf'] ) )
            $this->settings->mod_firewall_waf = $_POST['firewall_waf'] == "true" ? "true" : "false";
        if ( isset( $_POST['firewall_human-detection'] ) )
            $this->settings->mod_firewall_human_detection = $_POST['firewall_human-detection'] == "true" ? "true" : "false";
        if ( isset( $_POST['firewall_spam-registration'] ) )
            $this->settings->mod_firewall_spam_registration = $_POST['firewall_spam-registration'] == "true" ? "true" : "false";
        if ( isset( $_POST['2factor-authentication'] ) )
            $this->settings->mod_tfa_active = $_POST['2factor-authentication'] == "true" ? "true" : "false";
        if ( isset( $_POST['brute-force'] ) )
            $this->settings->mod_brute_force = $_POST['brute-force'] == "true" ? "true" : "false";
        if ( isset( $_POST['admin-protection'] ) )
            $this->settings->mod_admin_protection = $_POST['admin-protection'] == "true" ? "true" : "false";
        if ( isset( $_POST['suspicious-login'] ) )
            $this->settings->mod_suspicious_login = $_POST['suspicious-login'] == "true" ? "true" : "false";
        if ( isset( $_POST['scheduled-scans'] ) )
            $this->settings->mod_scheduled_scans = $_POST['scheduled-scans'] == "true" ? "true" : "false";
        if ( isset( $_POST['malware-clean'] ) )
            $this->settings->mod_malware_clean = $_POST['malware-clean'] == "true" ? "true" : "false";
        if ( isset( $_POST['analyst-service'] ) )
            $this->settings->mod_analyst_service = $_POST['analyst-service'] == "true" ? "true" : "false";

        $this->wp->wp_real_die();
    }

    public function submit_settings()
    {

        $nonce = $_POST['_wpnonce'];
        $action = 'submit_settings';

        if ( current_user_can('manage_options') && wp_verify_nonce( $nonce, $action ) ) {

            $this->settings->mod_firewall_active = $this->sanitize_setting_value('firewall');
            $this->settings->mod_firewall_fake_crawler = $this->sanitize_setting_value('firewall_fake-crawler');
            $this->settings->mod_firewall_known_attacks = $this->sanitize_setting_value('firewall_known-attacks');
            $this->settings->mod_firewall_human_detection = $this->sanitize_setting_value('firewall_human-detection');
            $this->settings->mod_firewall_spam_registration = $this->sanitize_setting_value('firewall_spam-registration');
            $this->settings->mod_firewall_anon_browsing = $this->sanitize_setting_value('firewall_anon-browsing');
            $this->settings->mod_tfa_active = $this->sanitize_setting_value('2factor-authentication');
            $this->settings->mod_brute_force = $this->sanitize_setting_value('brute-force');
            $this->settings->mod_admin_protection = $this->sanitize_setting_value('admin-protection');
            $this->settings->mod_suspicious_login = $this->sanitize_setting_value('suspicious-login');
            $this->settings->mod_scheduled_scans = $this->sanitize_setting_value('scheduled-scans');
            $this->settings->mod_malware_clean = $this->sanitize_setting_value('malware-clean');
            $this->settings->mod_analyst_service = $this->sanitize_setting_value('analyst-service');

            if ( strtolower($this->settings->mod_brute_force) == 'true' && isset( $_POST['brute-force-threshold'] ) ) {
                $this->settings->brute_force_threshold = abs( intval( $_POST['brute-force-threshold'] ) );
            }

            if ( strtolower($this->settings->mod_scheduled_scans) == 'true' && strtolower($this->settings->canScheduleScans) == 'true' ) {

                $this->settings->scheduled_scan_interval = abs( intval( $_POST['scheduled-scan-interval'] ) );

                if ( $this->check_time_value($_POST['scheduled-scan-time']) ) {
                    $this->settings->scheduled_scan_time = $_POST['scheduled-scan-time'];
                }
            }

            $tfa_settings = array(
                'delivery' => $this->wp->sanitize_text_field( $_POST['tfa_delivery'] ),
                'strictness' => $this->wp->sanitize_text_field( $_POST['tfa_strictness'] ),
                'email' => $this->wp->sanitize_email( $_POST['tfa_email'] ),
                'smsNumber' => $this->wp->sanitize_text_field( $_POST['tfa_phone'] ),
                'device-expiry-checked' => $_POST['tfa_expiry'],
                'device-expiry-days' => abs( intval( $_POST['tfa_expiry-days'] ) ),
            );

            $this->tfa_settings->update_settings( $this->wp->wp_get_current_user(), $tfa_settings);
        }

        $this->wp->wp_safe_redirect( urldecode( $this->wp->admin_url( 'admin.php?page=sabres' ) ) );
        $this->wp->wp_real_die();
    }

    public function cancel_settings() {
        $this->wp->wp_safe_redirect( urldecode( $this->wp->admin_url( 'admin.php?page=sabres' ) ) );
        $this->wp->wp_real_die();
    }

    public function load_features() {
        echo json_encode( $this->get_features() );
        $this->wp->wp_real_die();
    }

    public function reset_activation() {
        $this->settings->websiteServerToken = '';
        $this->settings->websiteClientToken = '';
        $this->activation->trigger_hourly_cron_now = true;
        $this->wp->wp_real_die();
    }

    public function reset_settings() {
        $user = $this->wp->wp_get_current_user();
        $this->tfa_settings->reset( $user );
        $this->settings->reload();
        $this->wp->wp_real_die();
    }

    public function is_sso_email() {
        if ($this->settings->sso_email)
            echo true;
        else
            echo false;

        $this->wp->wp_real_die();
    }

    public function is_premium() {
        if ( strtolower( $this->settings->isPremiumCustomer ) == "true" )
            echo true;
        else
            echo false;

        $this->wp->wp_real_die();
    }

    public function save_email() {
        $user = $this->wp->wp_get_current_user();
        $email = $this->wp->sanitize_email( $_POST['email'] );
        $tfa = $this->wp->sanitize_text_field( $_POST['tfa'] );

        if ( $tfa == 'true') {
            $email_settings = json_decode($this->wp->get_sabres_user_meta( $user->ID, 'tfa' ));
            $email_settings->email = $email;
            $this->wp->update_sabres_user_meta( $user->ID, 'tfa', json_encode($email_settings) );
        }

        if ( ! $this->settings->sso_email ) {
            $this->settings->sso_email = $email;
            $this->settings->sso_user = $user->user_login;
        }

        $this->wp->wp_real_die();
    }

    private function get_features() {

        $features['firewall'] = ! strcasecmp($this->settings->mod_firewall_active, "true");
        $features['firewall_fake-crawler'] = ! strcasecmp($this->settings->mod_firewall_fake_crawler, "true");
        $features['firewall_known-attacks'] = ! strcasecmp($this->settings->mod_firewall_known_attacks, "true");
        $features['firewall_human-detection'] = ! strcasecmp($this->settings->mod_firewall_human_detection, "true");
        $features['firewall_spam-registration'] = ! strcasecmp($this->settings->mod_firewall_spam_registration, "true");
        $features['firewall_anon-browsing'] = ! strcasecmp($this->settings->mod_firewall_anon_browsing, "true");
        $features['admin-protection'] = ! strcasecmp($this->settings->mod_admin_protection, "true");
        $features['2factor-authentication'] = ! strcasecmp($this->settings->mod_tfa_active, "true");
        $features['brute-force'] = ! strcasecmp($this->settings->mod_brute_force, "true");
        $features['suspicious-login'] = ! strcasecmp($this->settings->mod_suspicious_login, "true");
        $features['scheduled-scans'] = ! strcasecmp($this->settings->mod_scheduled_scans, "true");
        $features['malware-clean'] = ! strcasecmp($this->settings->mod_malware_clean, "true");
        $features['analyst-service'] = ! strcasecmp($this->settings->mod_analyst_service, "true");

        return $features;
    }

    public function get_default_features() {
        $defaults = $this->settings->get_default_settings();
        $features['firewall'] = ! strcasecmp($defaults['mod_firewall_active'], "true");
        $features['firewall_fake-crawler'] = ! strcasecmp($defaults['mod_firewall_fake_crawler'], "true");
        $features['firewall_known-attacks'] = ! strcasecmp($defaults['mod_firewall_known_attacks'], "true");
        $features['firewall_human-detection'] = ! strcasecmp($defaults['mod_firewall_human_detection'], "true");
        $features['firewall_spam-registration'] = ! strcasecmp($defaults['mod_firewall_spam_registration'], "true");
        $features['firewall_anon-browsing'] = ! strcasecmp($defaults['mod_firewall_anon_browsing'], "true");
        $features['admin-protection'] = ! strcasecmp($defaults['mod_admin_protection'], "true");
        $features['2factor-authentication'] = ! strcasecmp($defaults['mod_tfa_active'], "true");
        $features['brute-force'] = ! strcasecmp($defaults['mod_brute_force'], "true");
        $features['suspicious-login'] = ! strcasecmp($defaults['mod_suspicious_login'], "true");
        $features['scheduled-scans'] = ! strcasecmp($defaults['mod_scheduled_scans'], "true");
        $features['malware-clean'] = ! strcasecmp($defaults['mod_malware_clean'], "true");
        $features['analyst-service'] = ! strcasecmp($defaults['mod_analyst_service'], "true");

        echo json_encode($features);
        $this->wp->wp_real_die();
    }

    private function sanitize_setting_value( $name ){

        if ( isset( $_POST[ $name ] ) ) {
            if ( $_POST[ $name ] == "on" )
                return 'True';
        }

        return 'False';
    }

    function check_time_value($s) {

        if (preg_match('@^(\d\d):(\d\d)$@', $s, $m) == false) {
            return false;
        }

        if ( $m[4] >= 24 || $m[5] >= 60 ) {
            return false;
        }

        return true;
    }

    private function write_admin_data()
    {
        $client_token = $this->settings->websiteSabresClientToken;
        $api_token = $this->settings->apiKey;
        $is_first_activation = strcasecmp( $this->settings->first_activation, 'true' ) == 0;
        $is_premium = strcasecmp( $this->settings->isPremiumCustomer, 'true' ) == 0;
        $is_registered = $this->settings->sso_email != '';
        $sso_email = $this->settings->sso_email;

        $should_reactivate = $this->settings->should_trigger_activation();
        if ( $this->settings->https == '' || strcasecmp( $this->settings->https, 'true' ) == 0 ) {
            $admin_api_url = $this->ext_url_provider->get_admin_api_url();
            $portal_api_url = $this->ext_url_provider->get_portal_api_url();
            $base_portal_url = $this->ext_url_provider->get_base_portal_url();
        } else {
            $admin_api_url = $this->ext_url_provider->get_admin_api_url_plain();
            $portal_api_url = $this->ext_url_provider->get_portal_api_url_plain();
            $base_portal_url = $this->ext_url_provider->get_base_portal_url_plain();
        }

        $plugins_url=plugins_url('',__DIR__);

        echo <<<EOL
<script type="text/javascript">
    var sbs_admin_data = {
        clientToken:"$client_token",
        apiToken:"$api_token",
        adminApiURL:"$admin_api_url",
        portalApiURL:"$portal_api_url",
        basePortalURL:"$base_portal_url",
        isFirstActivation:"$is_first_activation",
        shouldReactivate:"$should_reactivate",
        isPremium:"$is_premium",
        isRegistered:"$is_registered",
        ssoEmail:"$sso_email",
        pluginURL:"$plugins_url"
    };
</script>
EOL;
    }

}
