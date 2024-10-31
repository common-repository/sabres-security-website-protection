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

require_once( __DIR__ . '/helpers/fail.php');

class SBRS_WP
{

    private $meta_key_prefix = 'sabres_';

    protected $privileged_caps = array(
        'create_sites',
        'delete_sites',
        'manage_network',
        'manage_sites',
        'manage_network_users',
        'manage_network_plugins',
        'manage_network_themes',
        'manage_network_options',
        'upload_plugins',
        'upload_themes',
        'activate_plugins',
        'create_users',
        'delete_plugins',
        'delete_themes',
        'delete_users',
        'edit_files',
        'edit_plugins',
        'edit_theme_options',
        'edit_themes',
        'edit_users',
        'export',
        'import',
        'install_plugins',
        'install_themes',
        'list_users',
        'manage_options',
        'promote_users',
        'remove_users',
        'switch_themes',
        'update_core',
        'update_plugins',
        'update_themes',
        'edit_dashboard',
        'customize',
        'delete_site'
    );


    public function intersect_admin_capabilities($capabilities)
    {
        return array_intersect($this->privileged_caps, $capabilities);

    }

    public function is_user_admin($user)
    {
        if (empty($user) || !($user instanceof \WP_User) || $user->ID == 0) {
            return false;
        }
        if (is_super_admin($user->ID))
            return true;
        return count($this->intersect_admin_capabilities(array_keys($user->allcaps))) > 0;
    }

    public function get_temp_dir()
    {
        return get_temp_dir();
    }

    public function wp_remote_retrieve_response_code($response)
    {
        return wp_remote_retrieve_response_code($response);
    }

    public function get_userdata($user_id)
    {
        return get_userdata($user_id);
    }

    public function get_user_by($field, $value)
    {
        return get_user_by($field, $value);
    }

    public function get_current_blog_id()
    {
        return get_current_blog_id();
    }

    public function get_bloginfo($show = '', $filter = 'raw')
    {
        return get_bloginfo($show, $filter);
    }

    public function wp_get_current_user()
    {
        return wp_get_current_user();
    }

    public function wp_safe_redirect($location, $status = 302)
    {
        require_once ABSPATH . 'wp-includes/pluggable.php';
        wp_safe_redirect($location, $status);
    }

    public function is_wp_error($thing)
    {
        return is_wp_error($thing);
    }

    public function is_admin()
    {
        return is_admin();
    }

    public function is_super_admin($user_id = false)
    {
        return is_super_admin($user_id);
    }

    public function get_role($role)
    {
        return get_role($role);
    }

    public function is_author($author = '')
    {
        return is_author($author);
    }

    public function register_activation_hook($file, $function)
    {
        register_activation_hook($file, $function);
    }

    public function register_deactivation_hook($file, $function)
    {
        register_deactivation_hook($file, $function);
    }

    public function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        return add_action($tag, $function_to_add, $priority, $accepted_args);
    }

    public function remove_action($tag, $function_to_remove, $priority = 10)
    {
        return remove_action($tag, $function_to_remove, $priority);
    }

    public function remove_filter($tag, $function_to_remove, $priority = 10)
    {
        return remove_filter($tag, $function_to_remove, $priority = 10);
    }

    public function do_action($tag, $arg = '')
    {
        do_action($tag, $arg);
    }

    public function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        return add_filter($tag, $function_to_add, $priority, $accepted_args);
    }

    public function has_filter($tag, $function_to_check = false)
    {
        return has_filter($tag, $function_to_check);
    }

    public function apply_filters($tag, $value)
    {
        return apply_filters($tag, $value);
    }

    public function is_rtl()
    {
        return is_rtl();
    }

    public function __($text, $domain = 'default')
    {
        return __($text, $domain);
    }

    public function _e($text, $domain = 'default')
    {
        _e($text, $domain);
    }

    public function esc_html__($text, $domain = 'default')
    {
        return esc_html__($text, $domain);
    }

    public function esc_html($text)
    {
        return esc_html($text);
    }

    public function esc_url($url, $protocols = null, $_context = 'display')
    {
        return esc_url($url, $protocols, $_context);
    }

    public function esc_attr($text)
    {
        return esc_attr($text);
    }

    public function esc_attr_e($text, $domain = 'default')
    {
        esc_attr_e($text, $domain);
    }

    public function is_email($email, $deprecated = false)
    {
        return is_email($email, $deprecated);
    }

    public function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all')
    {
        return wp_register_style($handle, $src, $deps, $ver, $media);
    }

    public function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
    {
        wp_enqueue_style($handle, $src, $deps, $ver, $media);
    }

    /**
     * WP function menu_page_url
     * @param $menu_slug
     * @param bool $echo
     */
    public function menu_page_url($menu_slug, $echo = true)
    {
        return menu_page_url($menu_slug, $echo);
    }

    public function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null)
    {
        return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
    }

    public function add_options_page($page_title, $menu_title, $capability, $menu_slug, $function = '')
    {
        return add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
    }

    public function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '')
    {
        return add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
    }

    public function get_plugins($plugin_folder = '')
    {
        if (!function_exists('get_plugins ')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return get_plugins($plugin_folder);
    }

    public function is_plugin_active($plugin)
    {
        if (!function_exists('is_plugin_active ')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($plugin);
    }

    public function activate_plugin($plugin, $redirect = '', $network_wide = false, $silent = false)
    {
        return activate_plugin($plugin, $redirect, $network_wide, $silent);
    }

    public function is_plugin_active_for_network($plugin)
    {
        return is_plugin_active_for_network($plugin);
    }

    public function deactivate_plugins($plugins, $silent = false, $network_wide = null)
    {
        deactivate_plugins($plugins, $silent, $network_wide);
    }

    public function wp_get_themes($args = array())
    {
        return wp_get_themes($args);
    }

    public function get_stylesheet()
    {
        return get_stylesheet();
    }

    /**
     * WP function plugins_url
     * @param string $path
     * @param string $plugin
     */
    public function plugins_url($path = '', $plugin = '')
    {
        return plugins_url($path, $plugin);
    }

    public function plugin_dir_path($file)
    {
        return plugin_dir_path($file);
    }

    public function plugin_basename($file)
    {
        return plugin_basename($file);
    }

    public function get_plugin_data($plugin_file, $markup = true, $translate = true)
    {
        return get_plugin_data($plugin_file, $markup, $translate);
    }

    public function download_url($url, $timeout = 300)
    {
        return download_url($url, $timeout);
    }

    public function get_template_directory()
    {
        return get_template_directory();
    }

    public function site_url($path = '', $scheme = null)
    {
        return site_url($path, $scheme);
    }

    public function get_site_url($blog_id = null, $path = '', $scheme = null)
    {
        return get_site_url($blog_id, $path, $scheme);
    }

    public function admin_url($path = '', $scheme = 'admin')
    {
        return admin_url($path, $scheme);
    }

    //Formating

    public function sanitize_email($email)
    {
        return sanitize_email($email);
    }

    public function sanitize_text_field($str)
    {
        return sanitize_text_field($str);
    }


    //Options

    public function  set_transient($transient, $value, $expiration = 0)
    {
        return set_transient($transient, $value, $expiration);
    }

    public function get_transient($transient)
    {
        return get_transient($transient);
    }

    public function delete_transient($transient)
    {
        return delete_transient($transient);
    }

    public function get_option($option, $default = false)
    {
        return get_option($option, $default);
    }

    public function add_option($option, $value = '', $deprecated = '', $autoload = 'yes')
    {
        return add_option($option, $value, $deprecated, $autoload);
    }

    public function delete_option($option)
    {
        return delete_option($option);
    }

    public function update_option($option, $value, $autoload = null)
    {
        return update_option($option, $value, $autoload);
    }

    //Capabilities

    public function current_user_can($capability)
    {
        return current_user_can($capability);
    }

    //File

    public function unzip_file($file, $to)
    {
        return unzip_file($file, $to);
    }

    public function WP_Filesystem($args = false, $context = false, $allow_relaxed_file_ownership = false)
    {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        return WP_Filesystem($args, $context, $allow_relaxed_file_ownership);
    }

    //Cron

    public function wp_clear_scheduled_hook($hook, $args = array())
    {
        wp_clear_scheduled_hook($hook, $args);
    }

    public function wp_get_schedule($hook, $args = array())
    {
        return wp_get_schedule($hook, $args);
    }

    public function wp_next_scheduled($hook, $args = array())
    {
        return wp_next_scheduled($hook, $args);
    }

    public function wp_schedule_event($timestamp, $recurrence, $hook, $args = array())
    {
        return wp_schedule_event($timestamp, $recurrence, $hook, $args);
    }


    public function wp_remote_post($url, $args = array())
    {
        return wp_remote_post($url, $args);
    }

    public function wp_redirect($location, $status = 302)
    {
        return wp_redirect($location, $status);
    }

    public function home_url($path = '', $scheme = null)
    {
        return home_url($path, $scheme);
    }

    public function wp_die($message = '', $title = '', $args = array())
    {
        wp_die($message, $title, $args);
    }

    public function wp_real_die($message = '')
    {
        die($message);
    }

    public function flush_rewrite_rules($hard = true)
    {
        flush_rewrite_rules($hard);
    }

    public function add_rewrite_rule($regex, $query, $after = 'bottom')
    {
        add_rewrite_rule($regex, $query, $after);
    }

    public function dbDelta($queries = '', $execute = true)
    {
        //this is allowed see:
        //https://codex.wordpress.org/Creating_Tables_with_Plugins
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        return dbDelta($queries, $execute);
    }

    public function wp_clear_auth_cookie()
    {
        wp_clear_auth_cookie();
    }

    public function wp_set_auth_cookie($user_id, $remember = false, $secure = '', $token = '')
    {
        wp_set_auth_cookie($user_id, $remember, $secure, $token);
    }

    public function  wp_mail($to, $subject, $message, $headers = '', $attachments = array())
    {
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    public function current_time($type, $gmt = 0)
    {
        return current_time($type, $gmt);
    }

    public function set_url_scheme($url, $scheme = null)
    {
        return set_url_scheme($url, $scheme);
    }

    public function add_query_arg()
    {
        $args = func_get_args();
        return call_user_func_array("add_query_arg", $args);
    }

    public function wp_remote_get($url, $args = array())
    {
        return wp_remote_get($url, $args);
    }

    public function  update_user_meta($user_id, $meta_key, $meta_value, $prev_value = '')
    {
        return update_user_meta($user_id, $meta_key, $meta_value, $prev_value);
    }

    public function get_user_meta($user_id, $key = '', $single = false)
    {
        return get_user_meta($user_id, $key, $single);
    }

    public function  update_sabres_user_meta($user_id, $meta_key, $meta_value, $prev_value = '')
    {
        return update_user_meta($user_id, $this->meta_key_prefix . $meta_key, $meta_value, $prev_value);
    }

    public function get_sabres_user_meta($user_id, $key = '', $single = false)
    {
        return get_user_meta($user_id, $this->meta_key_prefix . $key, $single);
    }

    public function delete_sabres_user_meta($user_id, $meta_key, $meta_value = '')
    {
        return delete_user_meta($user_id, $this->meta_key_prefix . $meta_key, $meta_value);
    }

    public function wp_cache_delete($key, $group = '')
    {
        return wp_cache_delete($key, $group);
    }

    public function query($query)
    {
        global $wpdb;
        return $wpdb->query($query);
    }

    public function prepare($query, $args)
    {
        global $wpdb;
        $args = func_get_args();

        return call_user_func_array(array($wpdb, 'prepare'), $args);
    }

    public function get_results($query = null, $output = OBJECT)
    {
        global $wpdb;
        return $wpdb->get_results($query, $output);
    }

    public function get_current_time()
    {
        global $wpdb;

        if (!isset($wpdb))
            SBRS_Helper_Fail::byeArr(array('message' => "wpdb global is not set",
                'code' => 500,
                'includeBacktrace' => true
            ));

        @$timestamp = $wpdb->get_var("SELECT NOW()");

        if ($this->is_wp_error($timestamp)) {
            SBRS_Helper_Fail::byeArr(array('message' => "Failed to get timestamp " . $timestamp->get_error_message(),
                'code' => 500,
                'includeBacktrace' => true,
                'logData' => $timestamp->get_error_data()
            ));
            return 0;
        }

        return strtotime($timestamp);
    }

    public function get_prefix()
    {
        global $wpdb;

        return $wpdb->prefix;
    }

    public function get_mail_err()
    {
        global $ts_mail_errors;
        global $phpmailer;
        if (!isset($ts_mail_errors)) $ts_mail_errors = array();
        if (isset($phpmailer)) {
            $ts_mail_errors[] = $phpmailer->ErrorInfo;
        }
        return print_r($ts_mail_errors, true);
    }

    public function is_user_logged_in()
    {
        return is_user_logged_in();
    }

    public function wp_login_url($redirect = '')
    {
        return wp_login_url($redirect);
    }

    public function get_permalink($post = 0, $leavename = false)
    {
        get_permalink($post, $leavename);
    }


    public function get_theme_root()
    {
        return get_theme_root();
    }

    public function get_wp_plugin_dir()
    {
        return WP_PLUGIN_DIR;
    }

    public function get_wp_version()
    {
        global $wp_version;

        return $wp_version;
    }

}
