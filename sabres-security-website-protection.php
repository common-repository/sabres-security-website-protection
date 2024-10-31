<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://sabressecurity.com
 * @since             0.1.0
 * @package           Sabres
 *
 * @wordpress-plugin
 * Plugin Name:       Sabres Security Website Protection
 * Plugin URI:        https://www.sabressecurity.com/my-account/
 * Description:       Sabres offers powerful and unique protection for your website. Protect against a wide array of bot and web-based attacks, and defend your small business website against hackers with our free WordPress plugin. With an ever-growing network of security, Sabres offers revolutionary defense against bots.
 * Version:           0.4.44
 * Author:            Sabres Security Ltd.
 * Author URI:        https://sabressecurity.com
 * Text Domain:       sabres-security-website-protection
 * Domain Path:       /languages
 * License: GPL2
 *
 * Copyright 2012  Sabres Security  (email : info@sabressecurity.com)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
require_once('classes/di.php');
require_once('classes/shutdown.php');

if (!defined('ABSPATH')) {
    die('Access denied.');
}

if (!defined('SABRES_CLS_DIR')) {
    define('SABRES_CLS_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . 'classes'));
}

if (!defined('SABRES_PATH')) {
    define('SABRES_PATH', __DIR__);
}

if (!defined('SABRES_VERSION')) {
    define('SABRES_VERSION', '0.4.44');
}

if (!defined('SABRES_PLUGIN_TYPE')) {
    define('SABRES_PLUGIN_TYPE', 'wp-store');
}


if (!defined('SABRES_DB_VERSION')) {
    define('SABRES_DB_VERSION', '0.2.9');
}

if (!defined('SABRES_PLUGIN_BASE_NAME')) {
    define('SABRES_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
}

require_once 'vendor/autoload.php';

$sabres_di = new SBRS_DI(plugin_basename(__FILE__));
$shutdown = new SBRS_Shutdown($sabres_di);
$shutdown->register();
$sabres_di->init_singletons();
$sabres_di->get_plugin()->register_hook_callbacks();
$sabres_di->get_plugin()->run($sabres_di->get_event_manager());
