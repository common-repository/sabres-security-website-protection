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

/* @var array $features */
$features = $this->get_features();

$gateway_connection = ! $this->settings->server_offline;
$service_activated = ! $this->settings->should_trigger_activation();
$traffic_monitor = $this->settings->isActive;
$tfa_settings = $this->tfa_settings->get_settings($this->wp->wp_get_current_user());
$is_premium = strtolower( $this->settings->isPremiumCustomer ) == "true";
$can_schedule_scans = strtolower( $this->settings->canScheduleScans ) == "true";
$registered = $this->settings->sso_email != '';

?>

<link href="<?php echo plugins_url('css/bootstrap.css', __DIR__); ?>" rel="stylesheet">
<link href="<?php echo plugins_url('css/style.css', __DIR__); ?>" rel="stylesheet">

<script src="<?php echo plugins_url('javascript/jquery.sabres.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/jquery.modalBox.min.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/sabres-settings.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/sabres-menu.js', __DIR__); ?>"></script>

<div class="container-fluid sabres-settings">
    <?php
    $this->write_admin_data();
    require('header.php');
    ?>
    <div class="row">
        <div class="page-title">
            <span class="icon"></span>
            <span class="title">Settings</span>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-10 col-md-9">
            <form id="settings-form" method="POST" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <div class="panel panel-settings">
                    <div class="panel-heading">
                        <span>Features</span>
                    </div>
                    <div class="panel-body">
                        <div class="setting-item">
                            <span class="feature-name">Firewall</span>
                            <label class="switch">
                                <input type="checkbox" class="parent-switch" name="firewall" id="firewall" <?php echo $features['firewall'] ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="child-settings" <?php echo $features['firewall'] ? '' : 'disabled' ?>>
                            <div class="setting-item">
                                <label class="square-switch">
                                    <input type="checkbox" name="firewall_fake-crawler" id="firewall_fake-crawler" <?php echo $features['firewall_fake-crawler'] ? 'checked' : '' ?>>
                                    <span class="square-box"></span>
                                </label>
                                <label for="firewall_fake-crawler">Fake crawler Protection</label>
                            </div>
                            <div class="setting-item">
                                <label class="square-switch">
                                    <input type="checkbox" name="firewall_known-attacks" id="firewall_known-attacks" <?php echo $features['firewall_known-attacks'] ? 'checked' : '' ?>>
                                    <span class="square-box"></span>
                                </label>
                                <label for="firewall_known-attacks">Known Attack Sources</label>
                            </div>
                            <div class="setting-item">
                                <label class="square-switch">
                                    <input type="checkbox" name="firewall_anon-browsing" id="firewall_anon-browsing" <?php echo $features['firewall_anon-browsing'] ? 'checked' : '' ?>>
                                    <span class="square-box"></span>
                                </label>
                                <label for="firewall_anon-browsing">Anonymous Browsing Protection</label>
                            </div>
                            <div class="setting-item">
                                <label class="square-switch">
                                    <input type="checkbox" name="firewall_spam-registration" id="firewall_spam-registration" <?php echo $features['firewall_spam-registration'] ? 'checked' : '' ?>>
                                    <span class="square-box"></span>
                                </label>
                                <label for="firewall_spam-registration">Spam protection</label>
                            </div>
                            <div class="setting-item">
                                <label class="square-switch">
                                    <input type="checkbox" name="firewall_human-detection" id="firewall_human-detection" <?php echo $features['firewall_human-detection'] ? 'checked' : '' ?>>
                                    <span class="square-box"></span>
                                </label>
                                <label for="firewall_human-detection">Human detection</label>
                            </div>
                        </div>
                        <div class="setting-item <?php echo $registered ? '' : 'disabled' ?>">
                            <span class="feature-name">Admin protection</span>
                            <label class="switch">
                                <input type="checkbox" class="parent-switch" name="admin-protection" id="admin-protection" <?php echo $features['admin-protection'] ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="child-settings <?php echo $features['admin-protection'] && $registered ? '' : 'disabled' ?>">
                            <div class="setting-item">
                                <label class="square-switch">
                                    <input type="checkbox" name="suspicious-login" id="suspicious-login" <?php echo $features['suspicious-login'] ? 'checked' : '' ?>>
                                    <span class="square-box"></span>
                                </label>
                                <label for="suspicious-login">Suspicious login alerts</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-settings tfa-panel-settings">
                    <div class="panel-heading">
                        <span>TFA</span>
                    </div>
                    <div class="panel-body <?php echo $registered ? '' : 'disabled' ?>">
                        <div class="setting-item">
                            <span class="feature-name">Enable Two Factor Authentication</span>
                            <label class="switch">
                                <input type="checkbox" class="parent-switch" name="brute-force" id="brute-force" <?php echo $features['brute-force'] ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="child-settings <?php echo $features['2factor-authentication'] ? '' : 'disabled' ?>">
                            <div class="setting-item">
                                <div class="setting-label">
                                    <span>Fake crawler protection</span>
                                </div>
                                <div class="setting-control">
                                    <div class="tfa-label-wrapper">
                                        <label class="round-switch">
                                            <input type="radio" name="tfa_delivery" id="tfa_delivery_email" value="email" <?php echo $tfa_settings['delivery'] == 'email' ? 'checked' : '' ?>>
                                            <span class="round-box"></span>
                                        </label>
                                        <label for="tfa_delivery_email">Send via Email</label>
                                    </div>

                                    <div class="tfa-label-wrapper">
                                        <label class="round-switch">
                                            <input type="radio" name="tfa_delivery" id="tfa_delivery_sms" value="sms" <?php echo $tfa_settings['delivery'] == 'sms' ? 'checked' : '' ?>>
                                            <span class="round-box"></span>
                                        </label>
                                        <label for="tfa_delivery_sms">Send via SMS</label>
                                    </div>

                                    <div class="tfa-label-wrapper">
                                        <label class="round-switch">
                                            <input type="radio" name="tfa_delivery" id="tfa_delivery_both" value="both" <?php echo $tfa_settings['delivery'] == 'both' ? 'checked' : '' ?>>
                                            <span class="round-box"></span>
                                        </label>
                                        <label for="tfa_delivery_both">Both</label>
                                    </div>
                                </div>
                            </div>
                            <div class="setting-item">
                                <div class="setting-label">
                                    <span>Email</span>
                                </div>
                                <div class="setting-control">
                                    <input type="email" name="tfa_email" id="tfa_email" placeholder="some@example.com" value="<?php echo $tfa_settings['email'] ?>">
                                </div>
                            </div>
                            <div class="setting-item">
                                <div class="setting-label">
                                    <span>Phone</span>
                                </div>
                                <div class="setting-control">
                                    <input type="tel" name="tfa_phone" id="tfa_phone" placeholder="+15552341234" value="<?php echo isset($tfa_settings['smsNumber']) ? $tfa_settings['smsNumber'] : '' ?>">
                                </div>
                            </div>
                            <div class="setting-item">
                                <div class="setting-label">
                                    <label for="tfa_strictness">Strictness</label>
                                </div>
                                <div class="setting-control">
                                    <div class="tfa-strictness-wrapper">
                                        <label class="round-switch">
                                            <input type="radio" name="tfa_strictness" id="strictness-new-device" value="new-device" <?php echo $tfa_settings['strictness'] == "new-device" ? 'checked' : '' ?>>
                                            <span class="round-box"></span>
                                        </label>
                                        <label for="tfa_strictness">Require two factor for new devices / browsers.</label>
                                    </div>
                                    <br>
                                    <label id="expiry-block" <?php echo $tfa_settings['strictness'] !== "new-device" ? 'class="disabled"' : '' ?> >
                                        <label class="square-switch">
                                            <input type="checkbox" name="tfa_expiry" id="tfa_expiry" <?php echo $tfa_settings['device-expiry-checked'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        Devices need to reauthenticate via two factor after <input type="number" id="tfa_expiry-days" name="tfa_expiry-days" value="<?php echo $tfa_settings['device-expiry-days'] ?>" maxlength="3" min="1" max="999" size="3">  days.
                                    </label>
                                    <br>
                                    <div class="tfa-strictness-wrapper">
                                        <label class="round-switch">
                                            <input type="radio" name="tfa_strictnes" id="strictness-new-device" value="every-login" <?php echo $tfa_settings['strictness'] == "every-login" ? 'checked' : '' ?>>
                                            <span class="round-box"></span>
                                        </label>
                                        <label for="tfa_strictnes">Every login requires two factor authentication.</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-settings brute-force-panel-settings">
                    <div class="panel-heading">
                        <span>Brute Force</span>
                    </div>
                    <div class="panel-body <?php echo $registered ? '' : 'disabled' ?>">
                        <div class="setting-item">
                            <span class="feature-name">Brute Force Protection</span>
                            <label class="switch">
                                <input type="checkbox" class="parent-switch" name="brute-force" id="brute-force" <?php echo $features['brute-force'] ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="child-settings <?php echo $features['brute-force'] ? '' : 'disabled' ?>">
                            <div class="setting-item">
                                <div class="setting-label">
                                    <label for="brute-force-threshold" class="feature-name">Brute Force Threshold</label>
                                </div>
                                <div class="setting-control">
                                    <input type="number" name="brute-force-threshold" id="brute-force-threshold" value="<?php echo $this->settings->brute_force_threshold ?>" maxlength="3" min="1" max="999" >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-settings scheduled-scans-panel-settings">
                    <div class="panel-heading">
                        <span>Scheduled Scans</span>
                    </div>
                    <div class="panel-body <?php echo $can_schedule_scans && $registered ? '' : 'disabled' ?>">
                        <div class="setting-item">
                            <span class="feature-name">Enable Scheduled Scans</span>
                            <label class="switch">
                                <input type="checkbox" class="parent-switch" name="scheduled-scans" id="scheduled-scans" <?php echo $features['scheduled-scans'] ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="child-settings <?php echo $features['scheduled-scans'] ? '' : 'disabled' ?>">
                            <div class="setting-item">
                                <div class="setting-label">
                                    <label for="scheduled-scan-interval" class="feature-name">Scheduled scan interval (days): </label>
                                </div>
                                <div class="setting-control">
                                    <input type="number" name="scheduled-scan-interval" id="scheduled-scan-interval" value="<?php echo $this->settings->scheduled_scan_interval ?>" maxlength="3" min="1" max="999" >
                                </div>
                            </div>
                            <div class="setting-item">
                                <div class="setting-label">
                                    <label for="scheduled-scan-time" class="feature-name">Scheduled scan time: </label>
                                </div>
                                <div class="setting-control">
                                    <input type="time" name="scheduled-scan-time" id="scheduled-scan-time" value="<?php echo $this->settings->scheduled_scan_time ?>" >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group settings-buttons">
                    <?php wp_nonce_field('submit_settings'); ?>
                    <button class="sabres-admin-btn" name="action" value="submit_settings" id="settings_submit_button">Save changes</button>
                    <button class="sabres-admin-btn" name="action" value="cancel_settings" id="settings_cancel_button">Cancel</button>
                </div>
            </form>
        </div>
        <div class="col-lg-2 col-md-3 hidden-sm">
            <?php require('banners.php') ?>
        </div>
    </div>
</div>