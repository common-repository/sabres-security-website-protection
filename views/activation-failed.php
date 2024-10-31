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

/*
$gateway_connection = strcasecmp($this->settings->server_offline,'true')!==0;
$service_activated = ! $this->settings->should_trigger_activation();
$traffic_monitor = strcasecmp($this->settings->isActive,true)!==0;
$is_activating=$this->wp->get_transient('sabres_activation_lock')!==false;
*/

$lastError = $this->logger->get_last_activation_error();

//$this->activation->check_server_token();
?>

<link href="<?php echo plugins_url('css/bootstrap.css', __DIR__); ?>" rel="stylesheet">
<link href="<?php echo plugins_url('css/style.css', __DIR__); ?>" rel="stylesheet">

<script src="<?php echo plugins_url('javascript/jquery.sabres.js', __DIR__); ?>"></script>
<?php $this->write_activation_data(); ?>
<script src="<?php echo plugins_url('javascript/activation-failed.js', __DIR__); ?>"></script>

<div class="container sabres-center">
    <div class="col-xl-8 col-lg-9 col-md-10 col-sm-11 col-xs-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="sabres-logo">
                    <img class="icon" src="<?php echo plugins_url('images/logo-default.png', __DIR__); ?>"
                         alt="Sabres" title="Sabres">
                </div>
                <ul class="service-status">
                    <li><span id="connection-to-gateway" class="check-mark" ></span>Connection to gateway</li>
                    <li><span id="service-activated" class="check-mark"></span>Service activated</li>
                    <li><span id="traffic-monitor" class="check-mark"></span>Traffic monitor</li>
                </ul>
                <?php /*
                <div class="activation-message">
                    We were unable to activate the plugin.
                    <?php if ( $lastError ) : ?>
                        <a class="show-error" href="#">Show details</a>
                        <p class="last-error"><?php echo $lastError ?></p>
                    <?php endif; ?>
                </div>
                 */ ?>
                <div class="activation-message" id="activation-message">
                </div>
                <div class="activation-button" id="activation-button">
                    <a class="sabres-admin-btn retry-btn" id="retry-activation" href="#">Retry Activation</a>
                </div>
            </div>
        </div>
    </div>
</div>
