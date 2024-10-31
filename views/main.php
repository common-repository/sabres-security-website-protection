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
?>

<link href="<?php echo plugins_url('css/bootstrap.css', __DIR__); ?>" rel="stylesheet">
<link href="<?php echo plugins_url('css/style.css', __DIR__); ?>" rel="stylesheet">
<link href="<?php echo plugins_url('css/nanoscroller.css', __DIR__); ?>" rel="stylesheet">

<script src="<?php echo plugins_url('javascript/circle-progress.min.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/progressbar.min.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/jquery.nanoscroller.min.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/jquery.modalBox.js', __DIR__); ?>"></script>

<script src="<?php echo plugins_url('javascript/jquery.sabres.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/sabres-admin.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/info-tip.js', __DIR__); ?>"></script>

<?php
$this->write_admin_data();

require('tour.php');
require_once( __DIR__ . '/../classes/helpers/info-texts.php');

$info_texts = new SBRS_Helper_Info_Texts();

/* @var array $features */
$features = $this->get_features();
$is_premium = strtolower($this->settings->isPremiumCustomer) == "true";
?>

<div class="container-fluid sabres-content">
    <?php require('header.php'); ?>
    <div class="row summary">
        <div class="col-lg-2 col-md-4 col-sm-3 col-xs-6">
            <div class="summary-panel panel panel-default">
                <div class="panel-body orange">
                    <span class="rating rating-danger pull-right"></span>
                    <span class="heading" id="summary_unresolved">-</span><br>
                    <span class="caption">Unsolved issues</span>
                    <img class="icon" src="<?php echo plugins_url('images/icons/attention.png', __DIR__); ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-3 col-xs-6">
            <div class="summary-panel panel panel-default">
                <div class="panel-body green">
                    <span class="rating rating-danger pull-right hidden"></span>
                    <span class="heading" id="summary_vulnerabilities">-</span><br>
                    <span class="caption">Vulnerabilities</span>
                    <img class="icon" src="<?php echo plugins_url('images/icons/vulnerabilities.png', __DIR__); ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-3 col-xs-6">
            <div class="summary-panel panel panel-default">
                <div class="panel-body red">
                    <span class="heading" id="summary_uptime">--<span>%</span></span><br>
                    <span class="caption">Uptime Monitor</span>
                    <img class="icon" src="<?php echo plugins_url('images/icons/health.png', __DIR__); ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-3 col-xs-6">
            <div class="summary-panel panel panel-default">
                <div class="panel-body orange">
                    <span class="heading" id="summary_humans">-</span><span class="second-value"><span id="bots_percent">-</span><span> bots</span></span><br>
                    <span class="caption">Humans Vs. Bots</span>
                    <img class="icon" src="<?php echo plugins_url('images/icons/traffic.png', __DIR__); ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-3 col-xs-6 hidden-md hidden-sm hidden-xs">
            <div class="summary-panel panel panel-default">
                <div class="panel-body green">
                    <span class="rating rating-danger pull-right"></span>
                    <span class="heading" id="summary_gbots">-</span><span class="second-value-wrapper"><span class="second-value"><span>Visits</span></span><span class="second-value"><span>Last visit </span><span>2/7/2017</span></span></span><br>
                    <span class="caption">Google bots</span>
                    <img class="icon" src="<?php echo plugins_url('images/icons/robot.png', __DIR__); ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-3 col-xs-6 hidden-md hidden-sm hidden-xs">
            <div class="summary-panel panel panel-default">
                <div class="panel-body red">
                    <span class="heading" id="summary_visitors">-</span><br>
                    <span class="caption">Visitors last 7 days</span>
                    <img class="icon" src="<?php echo plugins_url('images/icons/user.png', __DIR__); ?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-10 col-md-9 col-sm-12 no-pad">
        <div class="col-sm-12 no-pad">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-body panel-body-default">
                        <div class="row">
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div id="safety-percent"><p><span>-</span><i>%</i></p></div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 safety-prompt">
                                <div class="prompt-item" id="register-prompt">
                                    <h2>REGISTER</h2>
                                    <p>Register your e-mail now to complete your security profile!</p>
                                </div>
                                <div class="prompt-item" id="tfa-prompt">
                                    <h2>TFA</h2>
                                    <p>Set up two-factor authentication to ensure your website is protected.</p>
                                </div>
                                <div class="prompt-item" id="scan-prompt">
                                    <h2>Scan Now</h2>
                                    <p>Start your full security scan now to check your website for any vulnerabilities.</p>
                                </div>
                                <div class="prompt-item" id="flip-prompt">
                                    <div class="flip-control" id="flip-previous"></div>
                                    <div class="flip-control" id="flip-next"></div>
                                    <div class="flip-item" id="premium-prompt">
                                        <h2>Premium</h2>
                                        <p>Upgrade to premium now and get the best security to protect your website!</p>
                                    </div>
                                    <div class="flip-item" id="issue-prompt">
                                        <h2>Fix Issues</h2>
                                        <p>Resolve these issues to make sure your website is running as smoothly as possible.</p>
                                    </div>
                                    <div class="flip-item" id="vulnerabilities-prompt">
                                        <h2>Fix Vulnerabilities</h2>
                                        <p>Resolve these vulnerabilities now to optimize your website’s security and prevent attacks.</p>
                                    </div>
                                    <div class="flip-item" id="fake-crawler-prompt">
                                        <h2>Fake Crawler Protection</h2>
                                        <p>Please enable Fake Crawler Protection to avoid any harmful bots from stealing sensitive site data.</p>
                                    </div>
                                    <div class="flip-item" id="attack-sources-prompt">
                                        <h2>Known Attack Sources</h2>
                                        <p>Activate Known Attack Sources to expand your website’s security library and optimize your protection against common threats.</p>
                                    </div>
                                    <div class="flip-item" id="human-detection-prompt">
                                        <h2>Human Detection</h2>
                                        <p>Enable Human Detection now to ensure that only the right traffic is coming through by distinguishing human traffic from bots.</p>
                                    </div>
                                    <div class="flip-item" id="spam-protection-prompt">
                                        <h2>Spam Protection</h2>
                                        <p>Activate spam protection and stop any unwanted messages or comments on your site.</p>
                                    </div>
                                    <div class="flip-item" id="brute-force-prompt">
                                        <h2>Brute Force Alerts</h2>
                                        <p>Enable Brute Force Alerts to receive instant notifications if your website is under attack.</p>
                                    </div>
                                    <div class="flip-item" id="suspicious-login-prompt">
                                        <h2>Suspicious Login Alerts</h2>
                                        <p>Activate Suspicious login alerts to know when malicious traffic is attempting to visit your website without authorization.</p>
                                    </div>
                                    <div class="flip-item" id="scheduled-scans-prompt">
                                        <h2>Scheduled Scans</h2>
                                        <p>Set up scheduled scans to give your website a consistent web of defense against repeated or unexpected attacks.</p>
                                    </div>
                                    <div class="flip-item" id="anon-browsing-prompt">
                                        <h2>Anonymous Browsing Protection</h2>
                                        <p>Enable anonymous browsing protection and avoid any visitors from sources you can’t verify.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12">
                                <div class="scan-container">
                                    <div class="in-progress">
                                        <div class="scan-mount">&nbsp;</div>
                                        <div class="scan-bar">&nbsp;</div>
                                        <div class="scan-text">
                                            <span class="scan-title">Scanning in progress</span>
                                            <span class="scan-dots"></span>
                                        </div>
                                    </div>
                                    <a class="sabres-admin-btn scan-btn" href="#">Scan Now</a>
                                    <a class="scheduled-link" href="#">Want scheduled scans?</a>
                                </div>
                            </div>
                        </div>
                        <div class="row center">
                            <div class="step-wrapper">
                                <div id="step-complete-regitration" class="step-item item-1 <?php echo (!$this->settings->sso_email ? 'id-sabres-tour' : ''); ?>">
                                    <div id="step-complete-regitration-image" class="step-image <?php echo ($this->settings->sso_email ? 'complete' : ''); ?>"></div>
                                    <span>Complete registration</span>
                                </div>
                                <div id="step-admin-protection" class="step-item item-2 id-admin-protection <?php
                                echo (!$this->settings->sso_email ? 'id-sabres-tour' : '');
                                ?>">
                                    <div id="step-admin-protection-image" class="step-image <?php echo ($features['admin-protection'] ? 'complete' : ''); ?>"></div>
                                    <span>Enable admin protection</span>
                                </div>
                                <div id="step-hardning" class="step-item item-3 id-scan-now">
                                    <div  class="step-image"></div>
                                    <span>Hardening</span>
                                </div>
                                <div class="step-item item-4 id-premium-account" >
                                    <a href="https://www.sabressecurity.com/#complete-protection" target="_blank">
                                      <div class="step-image <?php echo ($is_premium ? 'complete' : ''); ?>"></div>
                                      <span>Upgrade account</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panels col-sm-12 no-pad">
                <div class="col-md-6 col-sm-12">
                    <div class="features-panel panel panel-default">
                        <div class="panel-heading">Quick Feature Management
                            <!-- <a class="settings" href="<?php echo admin_url('admin.php?page=sabres_settings'); ?>">Settings</a> -->
                        </div>
                        <div class="panel-body disabled">
                            <div class="features-group-firewall">
                                <div class="feature-item">
                                    <span class="feature-name">Firewall</span><span class="info-tip" data-text="<?php echo $info_texts->get('Firewall') ?>"></span>
                                    <label class="switch">
                                        <input type="checkbox" class="parent-switch" id="firewall" <?php echo $features['firewall'] ? 'checked' : '' ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="child-features">
                                    <div class="feature-item <?php echo $features['firewall'] ? '' : ' disabled-feature' ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="firewall_fake-crawler" <?php echo $features['firewall_fake-crawler'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="firewall_fake-crawler">Fake Crawler Protection</label>
                                    </div>
                                    <div class="feature-item <?php echo $features['firewall'] ? '' : ' disabled-feature' ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="firewall_known-attacks" <?php echo $features['firewall_known-attacks'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="firewall_known-attacks">Known Attack Sources</label><span class="info-tip" data-text="<?php echo $info_texts->get('Known Attack Sources') ?>"></span>
                                    </div>
                                    <div class="feature-item <?php echo $features['firewall'] ? '' : ' disabled-feature' ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="firewall_human-detection" <?php echo $features['firewall_human-detection'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="firewall_human-detection">Human detection</label><span class="info-tip" data-text="<?php echo $info_texts->get('Human Detection') ?>"></span>
                                    </div>
                                    <div class="feature-item <?php echo $features['firewall'] ? '' : ' disabled-feature' ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="firewall_spam-registration" <?php echo $features['firewall_spam-registration'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="firewall_spam-registration">Spam protection</label><span class="info-tip" data-text="<?php echo $info_texts->get('Spam Protection') ?>"></span>
                                    </div>
                                    <div class="feature-item <?php echo $features['firewall'] ? '' : ' disabled-feature' ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="firewall_anon-browsing" <?php echo $features['firewall_anon-browsing'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="firewall_anon-browsing">Anonymous Browsing Protection</label><span class="info-tip" data-text="<?php echo $info_texts->get('Anonymous Browser Protection') ?>"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="features-group-admin-protection">
                                <div id="admin-protection-quick-feature" class="feature-item <?php echo (!$this->settings->sso_email ? 'disabled-feature' : ''); ?>">
                                    <span class="feature-name">Admin protection</span>
                                    <label class="switch">
                                        <input type="checkbox" class="parent-switch" id="admin-protection" <?php echo ($features['admin-protection'] && $this->settings->sso_email) ? 'checked' : '' ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="child-features">
                                    <div class="feature-item <?php echo (!$features['admin-protection'] || !$this->settings->sso_email ? 'disabled-feature' : ''); ?>">
                                        <a href="#" id="tfa-pen" class="edit-pen"></a>
                                        <label class="square-switch">
                                            <input type="checkbox" id="2factor-authentication" <?php echo $features['2factor-authentication'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="2factor-authentication">2Factor Authentication</label><span class="info-tip" data-text="<?php echo $info_texts->get('TFA') ?>"></span>
                                    </div>
                                    <div class="feature-item <?php echo (!$features['admin-protection'] || !$this->settings->sso_email ? 'disabled-feature' : ''); ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="brute-force" <?php echo $features['brute-force'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="brute-force">Brute-Force</label><span class="info-tip" data-text="<?php echo $info_texts->get('Brute Force Attack') ?>"></span>
                                    </div>
                                    <div class="feature-item <?php echo (!$features['admin-protection'] || !$this->settings->sso_email ? 'disabled-feature' : ''); ?>">
                                        <label class="square-switch">
                                            <input type="checkbox" id="suspicious-login" <?php echo $features['suspicious-login'] ? 'checked' : '' ?>>
                                            <span class="square-box"></span>
                                        </label>
                                        <label for="suspicious-login">Suspicious login alerts</label><span class="info-tip" data-text="<?php echo $info_texts->get('Firewall') ?>"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="features-group-premium">
                                <div class="feature-item">
                                    <a href="#" class="edit-pen <?php if (!$is_premium): ?>disabled<?php endif; ?>"></a>
                                    <span class="feature-name">Scheduled scans</span>
                                    <?php if (!$is_premium): ?><span class="premium">Premium</span><?php endif; ?>
                                    <label class="switch <?php if (!$is_premium): ?>disabled<?php endif; ?>">
                                        <input type="checkbox" id="scheduled-scans" <?php echo $features['scheduled-scans'] ? 'checked' : '' ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="feature-item <?php if (!$features['malware-clean']): ?>disabled<?php endif; ?>">
                                    <span class="feature-name">Malware Clean Up</span>
                                    <?php if (!$is_premium): ?><span class="premium">Premium</span><?php endif; ?>
                                </div>
                                <div class="feature-item">
                                    <span class="feature-name">Investigate Traffic</span>
                                    <a id="investigate-traffic" href="#"
                                       class="go-to sabres-portal-link" data-portal-action="investigate-traffic">Go to</a>
                                </div>
                                <div class="feature-item <?php if (!$features['analyst-service']): ?>disabled<?php endif; ?>">
                                    <span class="feature-name">Analyst service</span>
                                    <?php if (!$is_premium): ?><span class="premium">Premium</span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="feed-panel panel panel-default">
                        <div class="feed-tabs">
                            <div class="feed-tabs-item active">
<!--                                <span class="info-tip" data-text="--><?php //echo $info_texts->get('Event Feed')  ?><!--"></span>-->
                                <span class="tab-title" id="events">Event Feed</span>
                            </div>
                            <div class="feed-tabs-item">
<!--                                <span class="info-tip" data-text="--><?php //echo $info_texts->get('Issues')  ?><!--"></span>-->
                                <span class="tab-title" id="issues">Issues<span id="issues-count"></span></span>
                            </div>
                            <div class="feed-tabs-item">
<!--                                <span class="info-tip" data-text="--><?php //echo $info_texts->get('Vulnerabilities')  ?><!--"></span>-->
                                <span class="tab-title" id="vulnerabilities">Vulnerabilities<span id="vulnerabilities-count"></span></span>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="feed-content nano active" id="feed-events">
                                <div class="nano-content">
                                    <ul class="timeline"></ul>
                                </div>
                            </div>
                            <div class="feed-content nano" id="feed-issues">
                                <div class="nano-content">
                                    <table class="data-table issues">
                                        <thead>
                                            <tr>
                <!--                                <th>Scan Type</th>-->
                                                <th>Type</th>
                                                <th>Risk</th>
                                                <th>Description</th>
        <!--                                        <th>Added</th>-->
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="feed-content nano" id="feed-vulnerabilities">
                                <div class="nano-content">
                                    <table class="data-table vulnerabilities">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Added</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="vulnerabilities-item">
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-3 hidden-sm hidden-xs">
        <?php require('banners.php') ?>
    </div>
</div>
