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

require_once( __DIR__ . '/scanner-engine.php' );
require_once( __DIR__ . '/../../helpers/io.php' );
require_once( __DIR__ . '/../../helpers/parser.php' );
require_once( __DIR__ . '/../../helpers/system.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );

class SBRS_Security_Scanner_Engine Extends SBRS_Scanner_Engine
{
    const WP_API_KEYS_URL = 'https://api.wordpress.org/secret-key/1.1/salt/';
    const WP_AUTO_UPDATE_CONST = 'WP_AUTO_UPDATE_CORE';
    const WP_COMMENT_APPROVAL = 'comment_moderation';
    const WP_COMMENT_USERS = 'comment_registration';

    const CHECK_HASH_SALT = 'a4d4f48051fc4b958b0fa24a88ae20c9';
    const DEFAULT_FILE_HASH_SALT = '3fb391966cf54d7fb9a83fb6366f3b68';

    static $WP_CONFIG_FILE_PATH;
    static $WP_README_FILE_PATH;
    static $WP_DEBUG_LOG_FILE_PATH;
    static $WP_INSTALL_FILE;

    private $debug_constants;
    private $error_settings;
    private $ssl_constants;
    private $checks;
    private $issues;

    protected $settings;
    /** @var  SBRS_WP */
    protected $wp;

    private $wp_folders;

    public function __construct($scan, $settings, $wp)
    {
        parent::__construct($scan);
        $this->settings = $settings;
        $this->wp = $wp;
        $this->wp_folders = array(
            'root' => ABSPATH,
            'admin' => ABSPATH . 'wp-admin',
            'content' => WP_CONTENT_DIR,
            'includes' => ABSPATH . 'wp-includes'
        );


        self::$WP_CONFIG_FILE_PATH = ABSPATH . 'wp-config.php';
        self::$WP_README_FILE_PATH = ABSPATH . 'readme.html';
        self::$WP_DEBUG_LOG_FILE_PATH = WP_CONTENT_DIR . '/debug.log';
        self::$WP_INSTALL_FILE = ABSPATH . 'wp-admin/install.php';

        $this->debug_constants = array(
            'WP_DEBUG',
            'WP_DEBUG_LOG',
            'WP_DEBUG_DISPLAY'
        );

        $this->error_settings = array(
            'log_errors',
            'display_errors'
        );

        $this->ssl_constants = array(
            'FORCE_SSL_LOGIN',
            'FORCE_SSL_ADMIN'
        );

        $this->checks = array(
            'readme' => array(
                'risk_level' => 1,
                'desc' => 'readme.html file found in root folder exposing version'
            ),
            'debug_file' => array(
                'risk_level' => 1,
                'desc' => 'debug.log file found at ' . self::$WP_DEBUG_LOG_FILE_PATH
            ),
            'debug_mode' => array(
                'risk_level' => 1,
                'desc' => 'debug mode is active'
            ),
            'login_errors' => array(
                'risk_level' => 2,
                'desc' => 'login errors are exposed'
            ),
            'xml_rpc' => array(
                'risk_level' => 3,
                'desc' => 'XML-RPC Protection is disabled'
            ),
            'click_jacking' => array(
                'risk_level' => 3,
                'desc' => 'site is vulnerable for click jacking'
            ),
            'install_file' => array(
                'risk_level' => 2,
                'desc' => 'file wp-admin/install.php exists'
            ),
            'author_archive' => array(
                'risk_level' => 3,
                'desc' => 'Author archive is visible'
            ),
            'api_keys' => array(
                'risk_level' => 2,
                'desc' => 'Security api keys are empty or invalid'
            ),
            'auto_update' => array(
                'risk_level' => 3,
                'desc' => 'Auto update is disabled'
            ),
            'tfa' => array(
                'risk_level' => 2,
                'desc' => 'Two-Factor authentication is disabled'
            ),
            'comment_approval' => array(
                'risk_level' => 2,
                'desc' => 'Comments are always approved'
            ),
            'comment_users' => array(
                'risk_level' => 2,
                'desc' => 'Anyone can post comments'
            ),
            'directory_listing' => array(
                'risk_level' => 3,
                'desc' => 'Directory listing is enabled'
            ),
            'error_handling' => array(
                'risk_level' => 2,
                'desc' => 'Errors are shown in public'
            ),
            'folder_permissions' => array(
                'risk_level' => 3,
                'desc' => 'Directory permissions are incorrect'
            ),
            'file_permissions' => array(
                'risk_level' => 3,
                'desc' => 'File permissions are incorrect'
            ),
            'ssl' => array(
                'risk_level' => 2,
                'desc' => 'SSL is disabled'
            )
        );
    }

    public function init($settings = null)
    {
        parent::init($settings);
    }

    public function is_valid()
    {
        $valid = parent::is_valid();

        return $valid;
    }

    private function get_check_hash($code)
    {
        return md5($code . self::CHECK_HASH_SALT);
    }

    public function start()
    {
        parent::start();

        $this->issues = array();

        foreach ($this->checks as $code => $check) {
            $function_name = "check_$code";

            try {
                if (method_exists($this, $function_name) && call_user_func(array($this, $function_name))) {
                    $issue = array(
                        'risk_level' => $check['risk_level'],
                        'code' => $code,
                        'desc' => $check['desc'],
                        'unique_id' => self::get_check_hash($code)
                    );

                    array_push($this->issues, $issue);

                    $this->event_trigger('issue', $issue);
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();

                $this->event_trigger('error', array(
                    'desc' => "$function_name: $message"
                ));
            }
        }

        parent::end();

        $issues_count = count($this->issues);

        $this->event_trigger('summary', array(
            'desc' => "$issues_count issues found"
        ));
    }

    public function fix_issue($code, $unique_id)
    {
        $check_hash = self::get_check_hash($code);

        if ($check_hash !== $unique_id) {
            throw new \Exception("Invalid id");
        }

        try {
            parent::fix_issue($code, $unique_id);

            $check_function_name = "check_$code";
            $fix_function_name = "fix_$code";


            if (method_exists($this, $check_function_name) && call_user_func(array($this, $check_function_name))) {
                if (method_exists($this, $fix_function_name)) {
                    return call_user_func(array($this, $fix_function_name));
                }
            } else {
                $this->event_trigger('error', array(
                    'desc' => 'Issue is already fixed'
                ));
            }
        } catch (\Exception $e) {
            $this->event_trigger('error', array(
                'desc' => $e->getMessage()
            ));
        }

        return false;
    }

    private function check_readme()
    {
        return @is_file(self::$WP_README_FILE_PATH);
    }

    private function fix_readme()
    {
        $file_path = self::$WP_README_FILE_PATH;

        if (!@is_file($file_path)) {
            throw new \Exception("File not found: $file_path");
        }

        $unique_id = md5(sha1(time()));
        $new_filepath = ABSPATH . "readme.$unique_id.html";

        @chmod($file_path, 644);

        if (!@rename($file_path, $new_filepath)) {
            throw new \Exception("Cannot rename file due to insufficent permissions: $file_path");
        }

        @chmod($file_path, 644);

        return true;
    }

    private function check_debug_file()
    {
        return @is_file(self::$WP_DEBUG_LOG_FILE_PATH);
    }

    private function fix_debug_file()
    {
        $file_path = self::$WP_DEBUG_LOG_FILE_PATH;

        if (!@is_file($file_path)) {
            throw new \Exception("File not found: $file_path");
        }

        @chmod($file_path, 644);

        if (!@unlink($file_path)) {
            throw new \Exception("Cannot delete file due to insufficent permissions: $file_path");
        }

        return true;
    }

    private function check_debug_mode()
    {
        $debug_constants = array_filter($this->debug_constants, function ($constant) {
            return defined($constant) && (!constant($constant) === false);
        });

        if (!empty($debug_constants)) return true;

        return false;
    }

    private function fix_debug_mode()
    {
        $this->settings->debug_mode = 'true';

        foreach ($this->debug_constants as $key) {
            SBRS_Helper_IO::patch_file(self::$WP_CONFIG_FILE_PATH, 'define', "'$key', false", "'$key'");
        }
    }

    private function check_error_handling()
    {
        foreach ($this->error_settings as $key) {
            $setting = trim(ini_get($key));

            if (strcasecmp($setting, 'on') == 0 || $setting == 1) return true;
        }

        return false;
    }

    private function fix_error_handling()
    {
        $this->settings->error_handling = 'true';

        SBRS_Helper_IO::patch_file(self::$WP_CONFIG_FILE_PATH, 'error_reporting', "0");

        foreach ($this->error_settings as $key) {
            SBRS_Helper_IO::patch_file(self::$WP_CONFIG_FILE_PATH, 'ini_set', "'$key', 0", "'$key'");
        }
    }

    private function check_login_errors()
    {
        return $this->wp->has_filter('login_errors');
    }

    private function check_apache_dir_listing()
    {
        if (!function_exists('apache_get_modules')) {
            throw new \Exception("Function does not exist: apache_get_modules");
        }

        $modules = apache_get_modules();
        if (!empty($modules) && is_array($modules) && in_array("mod_autoindex", $modules)) {
            return true;
        }

        return false;
    }

    private function check_xml_rpc()
    {
        $setting = $this->settings->mod_firewall_xml_rpc;

        if (!isset($setting) || strcasecmp($setting, 'true') != 0) {
            return true;
        }

        return false;
    }

    private function fix_xml_rpc()
    {
        $this->settings->mod_firewall_xml_rpc = 'true';

        return true;
    }

    private function check_click_jacking()
    {
        $setting = $this->settings->click_jacking;

        if (!isset($setting) || strcasecmp($setting, 'true') != 0) {
            return true;
        }

        return false;
    }

    private function fix_click_jacking()
    {
        $this->settings->click_jacking = 'true';

        return true;
    }

    private function check_install_file()
    {
        $file_path = self::$WP_INSTALL_FILE;

        return @file_exists($file_path);
    }

    private function fix_install_file()
    {
        $file_path = self::$WP_INSTALL_FILE;

        if (!@is_file($file_path)) {
            throw new \Exception("File not found: $file_path");
        }

        if (!@unlink($file_path)) {
            throw new \Exception("Cannot delete file due to insufficent permissions: $file_path");
        }

        return false;
    }

    private function check_author_archive()
    {
        $setting = $this->settings->author_archive;

        if (!isset($setting) || strcasecmp($setting, 'true') != 0) {
            return true;
        }

        return false;
    }

    private function fix_author_archive()
    {
        $this->settings->author_archive = 'true';

        return true;
    }

    private function check_api_key($key)
    {
        if (!defined($key)) {
            return true;
        }

        $value = constant($key);
        if (empty($value) || strlen($value) != 64) {
            return true;
        }

        return false;
    }

    private function check_api_keys()
    {
        return (
            $this->check_api_key('AUTH_KEY') ||
            $this->check_api_key('SECURE_AUTH_KEY') ||
            $this->check_api_key('LOGGED_IN_KEY') ||
            $this->check_api_key('NONCE_KEY') ||
            $this->check_api_key('AUTH_SALT') ||
            $this->check_api_key('SECURE_AUTH_SALT') ||
            $this->check_api_key('LOGGED_IN_SALT') ||
            $this->check_api_key('NONCE_SALT') ||
            $this->check_api_key('AUTH_KEY')
        );
    }

    private function fix_api_keys()
    {
        $url = $this->settings->wp_api_keys_url;

        if (empty($url)) {
            $url = self::WP_API_KEYS_URL;
        }

        $response = $this->wp->wp_remote_get($url, array(
            'method' => 'GET',
            'timeout' => 10,
            'redirection' => 5,
            'httpversion' => '1.0',
            'sslverify' => false,
            'blocking' => true,
            'headers' => array(),
            'body' => array(),
            'cookies' => array()
        ));

        if ($this->wp->is_wp_error($response)) {
            throw new \Exception("Server cannot be reached: $url");
        }

        if (empty($response['body'])) {
            throw new \Exception("Server response is empty: $url");
        }

        $response_body = $response['body'];

        $functions = SBRS_Helper_Parser::get_functions($response_body, 'define');
        list($functions_content, $functions_args) = $functions;

        for ($i = 0; $i < count($functions_content); $i++) {
            if (!empty($functions_content[$i]) && !empty($functions_args[$i])) {
                $function_args = $functions_args[$i][0];

                SBRS_Helper_IO::patch_file(self::$WP_CONFIG_FILE_PATH, 'define', $function_args, current(explode(',', $function_args)));
            }
        }
    }

    private function check_auto_update()
    {

        if (!defined(self::WP_AUTO_UPDATE_CONST)) {
            return true;
        }

        $value = constant(self::WP_AUTO_UPDATE_CONST);
        if (!$value) {
            return true;
        }

        $setting = $this->settings->auto_update;
        if (!isset($setting) || strcasecmp($setting, 'true') != 0) {
            return true;
        }

        return false;
    }

    private function fix_auto_update()
    {
        $this->settings->auto_update = 'true';
        $key = self::WP_AUTO_UPDATE_CONST;
        $file_path = self::$WP_CONFIG_FILE_PATH;

        $file_content = SBRS_Helper_IO::read_file($file_path);
        $functions = SBRS_Helper_Parser::get_functions($file_content, 'define', "'$key'");

        if (empty($functions[0]) && defined($key)) {
            throw new \Exception("Issue cannot be fixed: $key is defined outside of $file_path");
        }

        SBRS_Helper_IO::patch_file(self::$WP_CONFIG_FILE_PATH, 'define', "'$key', true", "'$key'");
    }

    private function check_tfa()
    {
        $setting = $this->settings->mod_tfa_active;

        if (!isset($setting) || strcasecmp($setting, 'true') != 0) {
            return true;
        }

        return false;
    }

    private function fix_tfa()
    {
        $this->settings->mod_tfa_active = 'true';

        return true;
    }

    private function check_comment_approval()
    {
        $option = $this->wp->get_option(self::WP_COMMENT_APPROVAL);

        if (empty($option)) {
            return true;
        }

        return false;
    }

    private function fix_comment_approval()
    {
        $this->wp->update_option(self::WP_COMMENT_APPROVAL, 1);
    }

    private function check_comment_users()
    {
        $option = $this->wp->get_option(self::WP_COMMENT_USERS);

        if (empty($option)) {
            return true;
        }

        return false;
    }

    private function fix_comment_users()
    {
        $this->wp->update_option(self::WP_COMMENT_USERS, 1);
    }

    private function check_folder_permissions()
    {
        if (!SBRS_Helper_System::is_cgi()) {
            throw new \Exception("Check can only be performed on CGI server php handler");
        }

        clearstatcache();

        $folders = SBRS_Helper_IO::get_folders($this->wp_folders, true, array($this->wp_folders['root']));

        foreach ($folders as $folder_path) {
            if (@is_dir($folder_path)) {
                $perms = @fileperms($folder_path);

                if ($perms) {
                    $perms = substr(decoct($perms), 3);

                    if ($perms != 755) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function fix_folder_permissions()
    {
        if (!SBRS_Helper_System::is_cgi()) {
            throw new \Exception("Fix can only be performed on CGI server php handler");
        }

        clearstatcache();

        $folders = SBRS_Helper_IO::get_folders($this->wp_folders, true, array($this->wp_folders['root']));

        foreach ($folders as $folder_path) {
            if (!@is_folder($folder_path)) {
                throw new \Exception("Cannot read folder due to insufficent permissions: $folder_path");
            }

            if (!@chmod($folder_path, 755)) {
                throw new \Exception("Cannot change folder due to insufficent permissions: $folder_path");
            }
        }

        return false;
    }

    private function check_file_permissions()
    {
        if (!SBRS_Helper_System::is_cgi()) {
            throw new \Exception("Check can only be performed on CGI server php handler");
        }

        clearstatcache();

        $files = array_merge(
            SBRS_Helper_IO::get_files(array($this->wp_folders['root'])),
            SBRS_Helper_IO::get_files($this->wp_folders, true, array($this->wp_folders['root']))
        );

        foreach ($files as $file_path) {
            if (@is_file($file_path)) {
                $perms = @fileperms($file_path);

                if ($perms) {
                    $perms = substr(decoct($perms), 3);

                    if ($perms != 644) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function fix_file_permissions()
    {
        if (!SBRS_Helper_System::is_cgi()) {
            throw new \Exception("Fix can only be performed on CGI server php handler");
        }

        clearstatcache();

        $files = array_merge(
            SBRS_Helper_IO::get_files(array($this->wp_folders['root'])),
            SBRS_Helper_IO::get_files($this->wp_folders, true, array($this->wp_folders['root']))
        );

        foreach ($files as $file_path) {
            if (!@is_file($file_path)) {
                throw new \Exception("Cannot read file due to insufficent permissions: $file_path");
            }

            if (!@chmod($file_path, 644)) {
                throw new \Exception("Cannot change file due to insufficent permissions: $file_path");
            }
        }

        return false;
    }

    private function check_ssl()
    {
        return false;
    }

    private function fix_ssl()
    {
        $this->settings->force_ssl = 'true';

        foreach ($this->ssl_constants as $key) {
            SBRS_Helper_IO::patch_file(self::$WP_CONFIG_FILE_PATH, 'define', "'$key', true", "'$key'");
        }
    }

    private function check_directory_listing()
    {
        clearstatcache();

        $folders = SBRS_Helper_IO::get_folders($this->wp_folders, true, array($this->wp_folders['root']));

        foreach ($folders as $folder_path) {
            if (@is_dir($folder_path)) {
                $files = SBRS_Helper_IO::get_files(array($folder_path));

                $default_files = array_filter($files, function ($file_path) {
                    $file_name = strtolower(pathinfo($file_path, PATHINFO_FILENAME));

                    return (in_array($file_name, array(
                        'index',
                        'default'
                    )));
                });

                if (empty($default_files)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function fix_directory_listing()
    {
        clearstatcache();

        $folders = SBRS_Helper_IO::get_folders($this->wp_folders, true, array($this->wp_folders['root']));

        foreach ($folders as $folder_path) {
            if (@is_dir($folder_path)) {
                $files = SBRS_Helper_IO::get_files(array($folder_path));

                $default_files = array_filter($files, function ($file_path) {
                    $file_name = strtolower(pathinfo($file_path, PATHINFO_FILENAME));

                    return (in_array($file_name, array(
                        'index',
                        'default'
                    )));
                });

                if (empty($default_files)) {
                    $hash = md5('Sabres' . self::DEFAULT_FILE_HASH_SALT);
                    SBRS_Helper_IO::write_file($folder_path . '/index.html', "Silence is golden.<br /><br />Powered by Sabres Security<br /><br />$hash\n");
                }
            }
        }
    }

}
