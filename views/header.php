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

$gateway_connection = !$this->settings->server_offline;
$service_activated = !$this->settings->should_trigger_activation();
$traffic_monitor = $this->settings->isActive;
?>
<div class="row">
    <div class="col-xl-3 col-lg-3 col-md-2 col-sm-12">
        <div class="top-logo">
            <img class="icon" src="<?php echo plugins_url('images/logo-default.png', __DIR__); ?>" alt="Sabres" title="Sabres">
        </div>
    </div>
    <div class="col-xl-9 col-lg-9 col-md-10 col-sm-12">
        <div id="sabres-mobile-menu" style="visibility:collapse"></div>
        <ul class="sabres-top-menu">
            <li class="support vertical dropdown">
                <a class="top-link support" href="#">Support</a>
                <div class="dropdown-content">
                    <ul>
                        <li class="letter">
                            <a href="mailto:support@sabressecurity.com">support@sabressecurity.com</a>
                        </li>
                        <li class="button">
                            <a class="dropdown-btn" id="sabres-tour" href="#">System tour</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="status-menu horizontal dropdown">
                <a class="top-link status" href="#">Service status</a>
                <div class="dropdown-content">
                    <ul>
                        <li><span class="check-mark <?php echo $gateway_connection ? 'ok' : 'error' ?>"></span>Connection to gateway</li>
                        <li><span class="check-mark <?php echo $service_activated ? 'ok' : 'error' ?>"></span>Service activated</li>
                        <li><span class="check-mark <?php echo $traffic_monitor ? 'ok' : 'error' ?>"></span>Traffic monitor</li>
                        <li><a class="sabres-admin-btn" id="reset-activation" href="#">Reset Activation</a></li>
                    </ul>
                </div>
            </li>
            <li class="reports-menu vertical dropdown">
                <a class="top-link reports" href="#">Reports</a>
                <div class="dropdown-content">
                    <ul>
                        <li>
                            <a id="menu-reports-activity" href="#" class="dropdown-btn sabres-portal-link" data-portal-action="activity-report">Activity</a>
                        </li>
                        <li>
                            <a id="menu-reports-status" href="#" class="dropdown-btn sabres-portal-link" data-portal-action="status-report">Status</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php /*
            <li class="glossary-menu vertical dropdown">
                <a class="top-link glossary" href="#">Glossary</a>
                <div class="dropdown-content">
                    <ul>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=sabres_glossary'); ?>"
                               class="dropdown-btn">Glossary of Security Terms</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="settings-menu vertical dropdown">
                <a class="top-link settings" href="<?php echo admin_url('admin.php?page=sabres_settings'); ?>">Settings</a>
            </li>
             */ ?>
        </ul>
    </div>
</div>
