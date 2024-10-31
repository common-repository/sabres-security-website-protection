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

require_once(__DIR__ . '/checkpost.php');
require_once(__DIR__ . '/checkrpc.php');
require_once(__DIR__ . '/checksqli.php');
require_once(__DIR__ . '/checkurl.php');
require_once(__DIR__ . '/checkxss.php');
require_once(__DIR__ . '/../firewall.php');
require_once(__DIR__ . '/../../plugin.php');
require_once(__DIR__ . '/../../request.php');
require_once(__DIR__ . '/../../settings.php');
require_once(__DIR__ . '/../../traffic-dispatcher.php');
require_once(__DIR__ . '/../../wp.php');
require_once(__DIR__ . '/../../helpers/cache.php');
require_once(__DIR__ . '/../../helpers/captcha.php');
require_once(__DIR__ . '/../../helpers/ip.php');
require_once(__DIR__ . '/../../helpers/network.php');
require_once(__DIR__ . '/../../helpers/server.php');
require_once(__DIR__ . '/../../helpers/utils.php');

define('SABRES_CAPTCHAS_EXPIRY', 60 * 4); //4 minutes
define('SABRES_MAX_CAPTCHAS_NUM', 80);  //No more then 80 captchas total per four minute period per single ip address

class SBRS_Firewall_Engine
{

    private $blacklist_ranges_filepath;
    private $blacklist_ranges;

    private $check_url;
    private $check_rpc;
    private $check_sqli;
    private $check_xss;

    /** @var SBRS_Settings */
    private $settings;
    /** @var SBRS_WP */
    private $wp;

    /** @var  SBRS_Request */
    private $request;

    /** @var  SBRS_Traffic_Dispatcher */
    private $dispatcher;

    /** @var  SBRS_Helper_Server */
    private $server;


    public function __construct(SBRS_Settings $settings, SBRS_WP $wp, $request, $dispatcher, $server)
    {
        $this->settings = $settings;
        $this->wp = $wp;
        $this->request = $request;
        $this->dispatcher = $dispatcher;
        $this->server = $server;

        $this->check_url = new SBRS_Firewall_Check_Url();
        $this->check_sqli = new SBRS_Firewall_Check_Sqli();
        $this->check_rpc = new SBRS_Firewall_Check_RPC($this->settings->mod_firewall_xml_rpc);
        $this->check_xss = new SBRS_Firewall_Check_XSS();
        $this->check_post = new SBRS_Firewall_CheckPOST();
    }

    public function process_request()
    {
        if (!$this->request->isRPC()) {
            $ip_array = SBRS_Helper_Network::get_all_ip_addresses();
            $unique_id = $this->request->getUniqueID();


            if (isset($this->check_rpc) && $this->check_rpc->check_request()) {
                $this->do_action(null, 'block', 'XMLRPC;');

                return;
            }

            if (isset($this->check_sqli) && $this->check_sqli->check_request()) {
                $this->do_action(null, 'block', 'SQLI;');

                return;
            }

            if (isset($this->check_xss) && $this->check_xss->check_request()) {
                $this->do_action(null, 'block', 'XSS;');

                return;
            }

            if (isset($this->check_url) && $this->check_url->check_request()) {
                $this->do_action(null, 'block', 'URL;');

                return;
            }

            $entries = $this->find_entries($ip_array, $unique_id);

            $blocked = null;
            $allowed = null;

            if ($this->settings->mod_firewall_country_mode === 'block_all') {
                $blocked = true;
            }

            if (!empty($entries)) {
                $allowed = $this->do_action($entries, $entries[0]->do_action, $entries[0]->description);
            }

            if (isset($blocked) && $blocked && !(isset($allowed) && $allowed)) {
                $this->do_action(null, 'block', 'CY_MODE;');
            }
        }
    }

    private function should_validate_post_request($user)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return false;
        if (defined('DOING_CRON'))
            return false;
        if ($this->wp->is_user_admin($user))
            return false;
        if (SBRS_Helper_Network::is_wp_login_action(array('login', 'logout'))) {
            return false;
        }
        return true;
    }

    private function get_table_for_entry($entry)
    {
        global $wpdb;
        $table_name = null;

        switch (strtoupper($entry->type)) {
            case 'CU':
                $table_name = $wpdb->prefix . 'sbs_firewall_custom';
                break;
            case 'CK':
                $table_name = $wpdb->prefix . 'sbs_firewall_cookies';
                break;
            case 'CY':
                $table_name = $wpdb->prefix . 'sbs_firewall_countries';
                break;
        }
        return $table_name;

    }

    private function delete_entry($entry)
    {
        global $wpdb;

        $table_name = $this->get_table_for_entry($entry);
        $sql = "DELETE FROM $table_name where id=" . (int)$entry->id;

        return $wpdb->query($sql);
    }

    private function update_captcha_to_solved($entry)
    {
        global $wpdb;
        $table_name = $this->get_table_for_entry($entry);
        $sql = "UPDATE $table_name SET description='" . $entry->description . ";SOLVED'" . " WHERE id=" . (int)$entry->id;

        return $wpdb->query($sql);

    }

    private function do_action($matching_entries, $action, $description)
    {
        if (defined('DOING_CRON') && ($action=='block' || $action=='captcha')) {
            $action='allow';
            $description='PCS;cron';
        }
        if ($action == 'allow') {
            $this->request->addRequestData(array(
                'firewall-action' => $this->getActionCode($action),
                'firewall-desc' => $description,
            ));

            return true;
        } else {
            SBRS_Helper_Cache::disable_cache();
            SBRS_Helper_Network::disable_cache();

            switch ($action) {
                case 'block':
                    $this->dispatch_request($action, $description);

                    $this->do_block("You are blocked. bye");
                    break;
                case 'captcha':
                    $captcha = 1;
                    $recurring = in_array('rcg', array_map('strtolower', explode(';', $description)));
                    $first_entry = $matching_entries[0];

                    $this->get_captcha_phrases($first_entry);
                    if (!empty($_POST['sbs_firewall_captcha_phrase']) && empty($_POST['sbs_firewall_captcha_refresh'])) {
                        $captcha_phrase = strtolower($_POST['sbs_firewall_captcha_phrase']);
                        if (isset($first_entry->phrases) && isset($first_entry->phrases[$captcha_phrase])) {
                            $captcha = 0;
                            if (!$recurring) {
                                foreach ($matching_entries as $entry) {
                                    if ($entry->do_action = 'captcha')
                                        $this->update_captcha_to_solved($entry);
                                }
                                if (isset($first_entry->phrases)) {
                                    $key = $this->build_captcha_phrase_key($first_entry);
                                    $this->wp->delete_transient($key);
                                }
                            }
                            $this->request->addRequestData(array(
                                'firewall-action' => 'CS',
                                'firewall-desc' => $description,
                            ));
                            break;
                        }
                    }

                    if ($captcha) {
                        $this->dispatch_request($action, $description);

                        if (!is_null($first_entry))
                            $this->do_captcha($first_entry);
                    }
                    break;
                case 'redirect':
                    $this->dispatch_request($action, $description);

                    global $wp_query;

                    $wp_query->set_404();

                    header('HTML/1.1 404 Not Found', true, 404);
                    header('Status: 404 Not Found');
                    @include($this->wp->get_template_directory() . '/404.php');
                    break;
            }
        }
    }

    private function do_captcha($entry)
    {
        $phrases = null;
        if (isset($entry->phrases))
            $phrases = $entry->phrases;
        else
            $phrases = array();
        $phrase_keys = array_keys($phrases);
        $current_time = time();
        $expired_found = false;
        foreach ($phrase_keys as $phrase_key) {
            if ($phrases[$phrase_key]['expiry'] < $current_time) {
                unset($phrases[$phrase_key]);
                $expired_found = true;
            }
        }
        $keyphrase = null;
        if (count($phrases) >= SABRES_MAX_CAPTCHAS_NUM) {
            if ($expired_found)
                $phrase_keys = array_keys($phrases);
            $keyphrase = $phrases[$phrase_keys[rand(0, count($phrases) - 1)]]['org_phrase'];
        }
        $captcha = SBRS_Helper_Captcha::get_captcha();
        $traffic_dispatcher = $this->dispatcher;

        if (!empty($captcha['keyphrase']) && !empty($captcha['captcha'])) {
            if (count($phrases) < SABRES_MAX_CAPTCHAS_NUM) {
                $phrases[strtolower($captcha['keyphrase'])] = array('expiry' => $current_time + SABRES_CAPTCHAS_EXPIRY, 'org_phrase' => $captcha['keyphrase']);
                $key = $this->build_captcha_phrase_key($entry);
                $this->wp->set_transient($key, json_encode($phrases), 60 * 10);
            }

            $captcha_data = $captcha['captcha'];
            $traffic_dispatcher->send_cookies();
            if (!empty($captcha_data)) {
                require_once SABRES_PATH . '/views/firewall/captcha.php';
            }
        }
        $this->wp->wp_real_die();
    }

    private function build_captcha_phrase_key($entry)
    {
        return 'sabres-captcha-phrase-' . $entry->type . '-' . $entry->id;
    }

    private function get_captcha_phrases(&$entry)
    {
        if ($entry->do_action = 'captcha') {
            $phrases = $this->wp->get_transient($this->build_captcha_phrase_key($entry));
            if ($phrases !== false) {
                $phrases = json_decode($phrases, true);
                if (!is_null($phrases))
                    $entry->phrases = $phrases;
            }
        }
    }

    private function do_block($message)
    {
        $this->dispatcher->send_cookies();
        $traffic_dispatcher = $this->dispatcher;

        header('Status: 503 Service Unavailable');

        require_once SABRES_PATH . '/views/firewall/block.php';

        $this->wp->wp_real_die();
    }

    private function delete_country_entry($ip)
    {
        global $wpdb;

        $sql = "DELETE FROM " . $wpdb->prefix . "sbs_firewall_countries where $ip >= " . $wpdb->prefix . "sbs_firewall_countries.from_ip AND $ip <= " . $wpdb->prefix . "sbs_firewall_countries.to_ip";

        return $wpdb->query($sql);
    }

    private function delete_custom_entry($from, $to = null)
    {
        global $wpdb;

        if (!$to) {
            $to = $from;
        }

        $sql = "DELETE FROM " . $wpdb->prefix . "sbs_firewall_custom where $from >= " . $wpdb->prefix . "sbs_firewall_custom.from_ip AND $to <= " . $wpdb->prefix . "sbs_firewall_custom.to_ip";

        return $wpdb->query($sql);
    }


    private function delete_cookie_entry($unique_id)
    {
        global $wpdb;

        $sql = "DELETE FROM " . $wpdb->prefix . "sbs_firewall_cookies where " . $wpdb->prefix . "sbs_firewall_cookies.unique_id = '$unique_id'";

        return $wpdb->query($sql);
    }

    private function dispatch_request($action, $description)
    {
        $this->dispatcher->dispatch_request(array(
            'firewall-action' => $this->getActionCode($action),
            'firewall-desc' => $description
        ));
    }

    private function getActionName($code)
    {
        switch (strtoupper($code)) {
            case 'A':
                return 'allow';
            case 'B':
                return 'block';
            case 'C':
                return 'captcha';
            case 'R':
                return 'redirect';
            case 'S':
                return 'special';
        }
    }

    private function getActionCode($name)
    {
        switch (strtolower($name)) {
            case 'allow':
                return 'A';
            case 'block':
                return 'B';
            case 'captcha':
                return 'C';
            case 'redirect':
                return 'R';
            case 'special':
                return 'S';
        }
    }

    public function add_country($code, $action, $description, $expiry = null)
    {
        // DB
        global $wpdb;

        return $wpdb->insert($wpdb->prefix . 'sbs_firewall_countries',
            array(
                'code' => $code,
                'do_action' => $this->getActionName($action),
                'description' => $description,
                'expiry' => !empty($expiry) ? $expiry : 'NULL'
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%d'
            )
        );
    }

    public function add_countries($data)
    {
        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'sbs_firewall_countries');

        if (!empty($data)) {
            $wpdb->query('BEGIN', $wpdb->dbh);

            foreach ($data as $item) {
                $this->add_country($item['code'], $item['action'], $item['desc'], !empty($item['expiry']) ? $item['expiry'] : null);
            }

            if (isset($error)) {
                $wpdb->query('ROLLBACK', $wpdb->dbh);
            } else {
                $wpdb->query('COMMIT', $wpdb->dbh);
            }
        }
    }

    public function add_custom($from, $to, $action, $description, $expiry = null, $global = null)
    {
        // DB
        global $wpdb;

        $this->delete_custom_entry($from, $to);

        return $wpdb->insert($wpdb->prefix . 'sbs_firewall_custom',
            array(
                'from_ip' => $from,
                'to_ip' => $to,
                'do_action' => $this->getActionName($action),
                'description' => $description,
                'expiry' => $expiry,
                'global_rule' => $global
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%s',
                '%d',
                '%d'
            )
        );
    }

    public function add_custom_range($data, $purge = null)
    {
        global $wpdb;

        if (!empty($purge)) {
            $wpdb->query('DELETE FROM ' . $wpdb->prefix . 'sbs_firewall_custom WHERE ' . $wpdb->prefix . 'sbs_firewall_custom.global_rule = 0');
        }

        if (!empty($data)) {
            $wpdb->query('BEGIN', $wpdb->dbh);

            foreach ($data as $item) {
                $expiry = null;
                if (!empty($item['expiry'])) {
                    $expiry = $item['expiry'];
                }

                if ($expiry == -1) {
                    $this->delete_custom_entry($item['from'], $item['to']);

                    continue;
                }

                $global = null;
                if (!empty($item['global'])) {
                    $global = $item['global'];
                } else {
                    $global = false;
                }

                $this->add_custom($item['from'], $item['to'], $item['action'], $item['desc'], $expiry, $global);
            }

            if (isset($error)) {
                $wpdb->query('ROLLBACK', $wpdb->dbh);
            } else {
                $wpdb->query('COMMIT', $wpdb->dbh);
            }
        }
    }

    public function add_unique_id($unique_id, $action, $description, $expiry)
    {
        // DB
        global $wpdb;

        $this->delete_cookie_entry($unique_id);

        return $wpdb->insert($wpdb->prefix . 'sbs_firewall_cookies',
            array(
                'unique_id' => $unique_id,
                'do_action' => $this->getActionName($action),
                'description' => $description,
                'expiry' => $expiry,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%d'
            )
        );
    }

    public function add_unique_ids($data)
    {
        global $wpdb;

        if (!empty($data)) {
            $wpdb->query('BEGIN', $wpdb->dbh);

            foreach ($data as $item) {
                $this->add_unique_id($item['uniqueId'], $item['action'], $item['desc'], !empty($item['expiry']) ? $item['expiry'] : null);
            }

            if (isset($error)) {
                $wpdb->query('ROLLBACK', $wpdb->dbh);
            } else {
                $wpdb->query('COMMIT', $wpdb->dbh);
            }
        }
    }

    private function get_matching_db_entries($ip_array = null, $unique_id = null)
    {
        $entries = null;
        global $wpdb;
        $cookies_table = $wpdb->prefix . 'sbs_firewall_cookies';
        $custom_table = $wpdb->prefix . 'sbs_firewall_custom';
        $country_table = $wpdb->prefix . 'sbs_firewall_countries';
        // DB
        $query = '';

        if (!empty($unique_id)) {

            $query = "SELECT 'CK' as type, 1 as 'priority',id, $cookies_table.do_action, $cookies_table.description, $cookies_table.expiry, $cookies_table.created_at
              FROM $cookies_table where $cookies_table.unique_id = '$unique_id'";
        }

        if (!empty($ip_array)) {
            $custom_where = '';
            $custom_count = 0;
            $country_count = 0;
            $country_where = '';
            foreach ($ip_array as $ip) {
                if ($custom_count > 0) {
                    $custom_where .= ' OR ';
                }
                $custom_where .= "($ip >= $custom_table.from_ip AND $ip <= $custom_table.to_ip)";
                $custom_count++;
                $ip_address = long2ip($ip);
                $country_code = SBRS_Helper_IP::get_country_code($ip_address);
                if (!empty($country_code)) {
                    if ($country_count > 0) {
                        $country_where .= ' OR ';
                    }
                    $country_where .= "('$country_code' = $country_table.code)";
                    $country_count++;
                }
            }
            if ($query != '') {
                $query .= ' UNION ALL ';
            }

            $query .= "SELECT 'CU' as type, 2 as 'priority',id, $custom_table.do_action, $custom_table.description,
              $custom_table.expiry, $custom_table.created_at
              FROM $custom_table where " . $custom_where;
            if ($country_count > 0) {
                $query .= ' UNION ALL ';
                $query .= "SELECT 'CY' as type, 3 as 'priority',id, $country_table.do_action, $country_table.description, $country_table.expiry,
                $country_table.created_at FROM $country_table where " . $country_where;
            }
        }


        if ($query != '') {
            $query = "SELECT * FROM (" . $query . ") firewall ORDER BY priority ASC, do_action ASC";
            global $wpdb;
            $entries = $wpdb->get_results($query, OBJECT);
        } else
            $entries = array();
        return $entries;
    }

    private function handle_entry_expiry($entry, $db_time, $unique_id, $user)
    {
        if (!empty($entry->expiry)) {
            $expiryTime = strtotime("+$entry->expiry seconds", strtotime($entry->created_at));

            if ($db_time >= $expiryTime) {
                $res = $this->delete_entry($entry);
                return true;
            } else if (strpos($entry->description, 'PCK;admin') === 0 && ($expiryTime - $db_time) < 179 * 24 * 60 * 60
                && $this->wp->is_user_admin($user)
            ) {
                //renew the cookie expiration once a day
                $this->delete_entry($entry);
                $this->new_admin_cookie($unique_id, $user);


            }
        }
        return false;
    }

    private function process_special_entry($entry)
    {
        switch ($entry->description) {
            case 'GL;SPECIAL;BLOCK_POST':
            case 'GL;SPECIAL;ALLOW_POST':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if ($entry->description === 'GL;SPECIAL;BLOCK_POST') {
                        if (SBRS_Helper_Network::is_wp_login_action(array('login', 'logout'))) {
                            $entry->do_action = 'allow';
                            $entry->description = 'PL' . substr($entry->description, 2);
                        } else
                            $entry->do_action = 'block';
                    }
                    if ($entry->description === 'GL;SPECIAL;ALLOW_POST')
                        $entry->do_action = 'allow';
                } else
                    return null;
                break;
            default:
                return null;
                break;
        }
        return $entry;
    }

    private function get_matching_db_entries_check_exp_and_process($ip_array = null, $unique_id = null, $user = null)
    {
        $db_entries = $this->get_matching_db_entries($ip_array, $unique_id);
        $matching_entries = array();
        $db_time = strtotime("now");
        if (!empty($db_entries)) {
            foreach ($db_entries as $entry) {
                if ($this->handle_entry_expiry($entry, $db_time, $unique_id, $user))
                    continue;
                if ($entry->do_action === 'special') {
                    $entry = $this->process_special_entry($entry);
                    if (!isset($entry))
                        continue;
                }
                array_push($matching_entries, $entry);
            }
        }
        return $matching_entries;

    }

    private function new_admin_cookie($unique_id, $user)
    {
        $cookie_setting = array('uniqueId' => $unique_id, 'action' => 'A', 'desc' => 'PCK;admin;' . $user->user_login, 'expiry' => 180 * 24 * 60 * 60);
        $this->add_unique_ids(array($cookie_setting));
        return $cookie_setting;
    }

    private function check_admin_user_cookie($unique_id, $user)
    {
        if (!isset($unique_id))
            return null;
        if ($user instanceof \WP_User && $this->wp->is_user_admin($user)) {
            $counter_key = 'sabres_acc_' . $unique_id;
            $admin_cookie_counter = $this->wp->get_transient($counter_key);
            if (!isset($admin_cookie_counter) || $admin_cookie_counter === false)
                $admin_cookie_counter = 0;
            $admin_cookie_counter++;
            $this->wp->set_transient($counter_key, $admin_cookie_counter, 60 * 60);
            $result = new \stdClass();
            $result->type = 'DY';
            $result->priority = 0;
            $result->id = 0;
            $result->do_action = 'allow';
            $result->description = 'PL;admin;' . $user->user_login;
            $result->expiry = 0;
            $result->created_at = date("Y-m-d H:i:s");
            if ($admin_cookie_counter > 1) {
                //if cookie has at least two requests constuct new cookie firewall definition and save it
                $cookie_setting = $this->new_admin_cookie($unique_id, $user);
                $result->type = 'CK';
                $result->description = $cookie_setting['desc'];
                $this->wp->delete_transient($counter_key);
            }
            return $result;
        }
        return null;
    }

    private function find_entries($ip_array = null, $unique_id = null)
    {
        $user = $this->wp->wp_get_current_user();
        $entries = $this->get_matching_db_entries_check_exp_and_process($ip_array, $unique_id, $user);
        $first_entry = !empty($entries) ? $entries[0] : null;
        if (!isset($first_entry) || $first_entry->type !== 'CK') {
            //no directive at the cookie level
            $first_entry = $this->check_admin_user_cookie($unique_id, $user);
            if (isset($first_entry))
                array_unshift($entries, $first_entry);
        }

        if (empty($entries) && $this->should_validate_post_request($user)) {
            $check_post = $this->check_post;
            $check_post_response = $check_post->check_request($unique_id, $this->settings, $this->server);
            if (isset($check_post_response['firewallSetting'])) {
                $this->add_custom_range(array($check_post_response['firewallSetting']));
                $entries = $this->get_matching_db_entries_check_exp_and_process($ip_array, $unique_id, $user);
            }
        }
        //if first match is due to solved captcha remove entry. Order is of importance here because we don't want to validate post
        //if captcha was solved
        $first_entry = !empty($entries) ? $entries[0] : null;
        if (isset($first_entry) && $first_entry->do_action === 'captcha' && SBRS_Helper_Utils::string_ends_with($first_entry->description, ';SOLVED')) {
            array_shift($entries);
        }
        return $entries;
    }

    private function store_blacklist_ranges()
    {
        if ($this->blacklist_ranges_filepath != '') {
            if (@file_put_contents($this->blacklist_ranges_filepath, serialize($this->blacklist_ranges))) {
            }
        }
    }

    public function block_range($from, $to, $action, $expiry)
    {

    }

}
