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

class SBRS_Auto_Update extends SBRS_Module
{
  private $request;

  private $wp;

  private $logger;

  /**
 * The plugin current version
 * @var string
 */
 private $current_version;
/**
 * The plugin remote update path
 * @var string
 */
 private $update_path;
/**
 * Plugin Slug (plugin_directory/plugin_file.php)
 * @var string
 */
 private $plugin_slug;
/**
 * Plugin name (plugin_file)
 * @var string
 */
 private $slug;
/**
 * License User
 * @var string
 */
 private $license_user;
/**
 * License Key
 * @var string
 */
private $license_key;
/**
 * Initialize a new instance of the WordPress Auto-Update class
 * @param string $current_version
 * @param string $update_path
 * @param string $plugin_slug
 */
  public function __construct($plugin_slug,SBRS_WP $wp,SBRS_Request $request,$ext_url_provider,SBRS_Logger $logger)
  {
    $this->request=$request;
    $this->wp=$wp;
    $this->logger=$logger;
    // Set the class public variables
    $this->current_version = SABRES_VERSION;
    $this->update_path = $ext_url_provider->get_plugin_download_url();
    // Set the License
    $this->license_user = '';
    $this->license_key = '';
    // Set the Plugin Slug
    $this->plugin_slug = $plugin_slug;
    list ($t1, $t2) = explode( '/', $plugin_slug );
    $this->slug = str_replace( '.php', '', $t2 );
  }
  public function is_enabled()
  {

    return !$this->request->isRPC();
  }

  public function register_hook_callbacks()
  {
      // define the alternative API for updating checking
      $this->wp->add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );
      // Define the alternative response for information checking
      $this->wp->add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );
  }
  /**
   * Add our self-hosted autoupdate plugin to the filter transient
   *
   * @param $transient
   * @return object $ transient
   */
  public function check_update( $transient )
  {
      if ( empty( $transient->checked ) ) {
          return $transient;
      }
      // Get the remote version
      $remote_version = $this->getRemote('version');
      // If a newer version is available, add the update
      if ($remote_version && version_compare( $this->current_version, $remote_version->new_version, '<' ) ) {
          $obj = new stdClass();
          $obj->slug = $this->slug;
          $obj->new_version = isset($remote_version->new_version) ? $remote_version->new_version : '';
          $obj->url = isset($remote_version->url) ? $remote_version->url : '';
          $obj->plugin = isset($this->plugin_slug) ? $this->plugin_slug : '';
          $obj->package = isset($remote_version->package) ? $remote_version->package : '';
          $obj->tested = isset($remote_version->tested) ? $remote_version->tested : '';
          $transient->response[$this->plugin_slug] = $obj;
      }
      return $transient;
  }
  /**
   * Add our self-hosted description to the filter
   *
   * @param boolean $false
   * @param array $action
   * @param object $arg
   * @return bool|object
   */
  public function check_info($obj, $action, $arg)
  {
      if (($action=='query_plugins' || $action=='plugin_information') &&
          isset($arg->slug) && $arg->slug === $this->slug) {
          return $this->getRemote('info');
      }

      return $obj;
  }
  /**
   * Return the remote version
   *
   * @return string $remote_version
   */
  public function getRemote($action = '')
  {
      $params = array(
          'body' => array(
              'action'       => $action,
              'license_user' => $this->license_user,
              'license_key'  => $this->license_key,
          ),
      );

      // Make the POST request
      $request = $this->wp->wp_remote_post($this->update_path, $params );      

      // Check if response is valid
      if ( !$this->wp->is_wp_error( $request ) || $this->wp->wp_remote_retrieve_response_code( $request ) === 200 ) {
          return @unserialize( $request['body'] );
      }
      else
        $this->logger->log('error', 'Auto Update', 'Failed to check for version update to : '.$this->update_path, $request);



      return false;
  }

}
