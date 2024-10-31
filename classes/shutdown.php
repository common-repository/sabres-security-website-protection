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

require_once( __DIR__ . '/di.php');
require_once( __DIR__ . '/logger.php');
require_once( __DIR__ . '/settings.php');
require_once( __DIR__ . '/helpers/server.php');
require_once( __DIR__ . '/helpers/utils.php');

class SBRS_Shutdown
{

    /** @var SBRS_DI */
    private $di;


    public function __construct(SBRS_DI $di)
    {
        $this->di = $di;

    }

    public function register()
    {
        register_shutdown_function(array($this, 'shutdown'));
    }

    public function shutdown()
    {
        $error = error_get_last();
        if (!empty($error['type'])) {

            $error_type = $error['type'];
            $error_bad = in_array($error_type, array(
                E_ERROR,
                E_CORE_ERROR,
                E_COMPILE_ERROR,
                E_USER_ERROR,
                E_RECOVERABLE_ERROR
            ));

            if ($error_bad && !empty($error['file'])) {
                $error_file = $error['file'];
                $error_dir = dirname($error_file);
                $current_dir = dirname(__FILE__);

                if ((stripos($error_file, SABRES_PATH) !== false)) {

                    ob_start();
                    debug_print_backtrace();
                    $trace = ob_get_contents();
                    ob_end_clean();
                    @file_put_contents(SABRES_PATH . '/activation.log.txt', date("Y-m-d H:i:s") . ' Plugin is shutting down due to an unexpected error: ' . var_export($error, true) . PHP_EOL . $trace . PHP_EOL, FILE_APPEND | LOCK_EX);

                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                    deactivate_plugins(SABRES_PLUGIN_BASE_NAME, true);

                    $data = array();
                    if ($this->di->get_settings() instanceof SBRS_Settings) {
                        $data['websiteServerToken'] = $this->di->get_settings()->websiteSabresServerToken;
                    }

                    $data['reason'] = 'error';
                    $data = array_merge($error, $data);
                    $data['type'] = SBRS_Helper_Utils::friendly_error_type($data['type']);

                    try {
                        if ($this->di->get_server() instanceof SBRS_Helper_Server) {
                            $this->di->get_server()->call('plugin-deactivated', '', $data);
                        }
                        if ($this->di->get_logger() instanceof SBRS_Logger) {
                            $this->di->get_logger()->log('error', 'shutdown', $error['file'], $data);
                        }
                    } catch (\Exception $e) {

                    }


                }
            }
        }
    }

}
