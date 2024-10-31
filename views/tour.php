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

<script src="<?php echo plugins_url( 'javascript/jquery.foggy.min.js', __DIR__ ); ?>"></script>
<script src="<?php echo plugins_url( 'javascript/sabres-tour.js', __DIR__ ); ?>"></script>

<div class="overlay"></div>
<div class="tour-content">
    <div class="tour-step fifth-step">
        <div class="hint-container first-hint stretch-hint round-hint angle-view"></div>
        <div class="hint-container second-hint stretch-hint round-hint angle-view"></div>
<!--        <div class="hint-container third-hint round-hint menu-hint"></div>-->
        <div class="tour-block header">
            <h2>Learn how to use sabres</h2>
            <p>Basic features for you to learn</p>
        </div>
<!--        <div class="tour-block third">-->
<!--            <img src="--><?php //echo plugins_url('images/tour-arrows/page5-settings.png', __DIR__); ?><!--">-->
<!--            <h3>Full features management</h3>-->
<!--            <p>Customize your settings as you will</p>-->
<!--        </div>-->
        <div class="tour-block second">
            <img src="<?php echo plugins_url('images/tour-arrows/page5-feed.png', __DIR__); ?>">
            <h3>System status feed</h3>
            <p>Shows website threats and vulnerabilities</p>
        </div>
        <div class="tour-block first">
            <img src="<?php echo plugins_url('images/tour-arrows/page5-features.png', __DIR__); ?>">
            <h3>Quick features management</h3>
            <p>To handle major issues quick</p>
        </div>

        <div class="progress">
            <div class="progress-btn back">
                <span>Back</span>
                <span class="progress-arrow"></span>
            </div>
            <div class="progress-indicator">
                <span class="progress-circle active"></span>
                <span class="progress-circle active"></span>
                <span class="progress-circle active"></span>
                <span class="progress-circle active"></span>
                <span class="progress-circle active"></span>
            </div>
            <div class="progress-btn finish">
                <span class="progress-arrow"></span>
                <span>Finish</span>
            </div>
        </div>
    </div>
    <div class="tour-step fourth-step">
        <div class="hint-container first-hint stretch-hint"></div>
        <div class="hint-container second-hint round-hint"></div>
        <div class="tour-block header">
            <h2>Learn how to use sabres</h2>
            <p>Basic features for you to learn</p>
        </div>
        <div class="tour-block first">
            <img src="<?php echo plugins_url('images/tour-arrows/page4-indicators.png', __DIR__); ?>">
            <h3>Dashboard status</h3>
            <p>Learn your website status</p>
        </div>
        <div class="tour-block second">
            <img src="<?php echo plugins_url('images/tour-arrows/page4-scan.png', __DIR__); ?>">
            <h3>System scan button</h3>
            <p>We recommend you scan your website every day</p>
        </div>

        <div class="progress-container">
        <div class="progress">
              <div class="progress-btn back">
                  <span>Back</span>
                  <span class="progress-arrow"></span>
              </div>
              <div class="progress-indicator">
                  <span class="progress-circle active"></span>
                  <span class="progress-circle active"></span>
                  <span class="progress-circle active"></span>
                  <span class="progress-circle active"></span>
                  <span class="progress-circle"></span>
              </div>
              <div class="progress-btn next">
                  <span class="progress-arrow"></span>
                  <span>Next</span>
              </div>
           </div>
           <div class="skip-btn">SKIP</div>
        </div>
    </div>
    <div class="tour-step third-step">
<!--        <div class="hint-container first-hint round-hint menu-hint borderless"></div>-->
        <div class="tour-block first">
            <h2>Security education</h2>
            <h3>Look for the "<img src="<?php echo plugins_url('images/info-icon.png', __DIR__); ?>">" icon</h3>
            <p>It will open a tooltip with the term explanation</p>
        </div>
<!--        <div class="tour-block second">-->
<!--            <img src="--><?php //echo plugins_url('images/tour-arrows/page3-glossary.png', __DIR__); ?><!--">-->
<!--            <h3>All terms combined</h3>-->
<!--            <p>For you convenience</p>-->
<!--        </div>-->

        <div class="progress-container">
            <div class="progress">
                <div class="progress-btn back">
                    <span>Back</span>
                    <span class="progress-arrow"></span>
                </div>
                <div class="progress-indicator">
                    <span class="progress-circle active"></span>
                    <span class="progress-circle active"></span>
                    <span class="progress-circle active"></span>
                    <span class="progress-circle"></span>
                    <span class="progress-circle"></span>
                </div>
                <div class="progress-btn next">
                    <span class="progress-arrow"></span>
                    <span>Next</span>
                </div>
            </div>
            <div class="skip-btn">SKIP</div>
        </div>
    </div>
    <div class="tour-step second-step">
        <div class="tour-block">
            <h1>Hurray! You are now connected</h1>
            <h3>Now you just got full access to our website</h3>
            <p>Access our website for advanced settings and reports for more information: www.sabressecurity.com</p>
        </div>
        <div class="tour-block tour-slider" id="tour-slider-1">
            <div class="tour-slider-content plugin-screen">
                <p>Sabres plugin</p>
                <img src="<?php echo plugins_url('images/plugin-screen.png', __DIR__); ?>">
            </div>
            <div class="tour-slider-content slider-arrow">
                <img src="<?php echo plugins_url('images/tour-arrows/straight.png', __DIR__); ?>">
            </div>
            <div class="tour-slider-content portal-screen">
                <p>Sabres portal</p>
                <img src="<?php echo plugins_url('images/portal-screen.png', __DIR__); ?>">
            </div>
        </div>
        <div class="tour-block tour-slider" id="tour-slider-2">
            <div class="tour-slider-content portal-screen">
                <img src="<?php echo plugins_url('images/portal-screen.png', __DIR__); ?>">
            </div>
            <div class="tour-slider-content portal-screen">
                <div class="slider-list">
                    <ul>
                        <li>Manage advanced security settings</li>
                        <li>Live traffic graphs and alerts</li>
                        <li>Create custom black list and country blocking</li>
                        <li>Combines all of your websites</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="tour-block">
            <h3>You will be redirected automatically when necessary</h3>
            <div class="slider-indicator">
                <span class="slider-circle active" id="slider-control-1"></span>
                <span class="slider-circle" id="slider-control-2"></span>
            </div>
        </div>

        <div class="progress-container">
          <div class="progress">
              <div class="progress-btn back">
                  <span>Back</span>
                  <span class="progress-arrow"></span>
              </div>
              <div class="progress-indicator">
                  <span class="progress-circle active"></span>
                  <span class="progress-circle active"></span>
                  <span class="progress-circle"></span>
                  <span class="progress-circle"></span>
                  <span class="progress-circle"></span>
              </div>
              <div class="progress-btn next">
                  <span class="progress-arrow"></span>
                  <span>Next</span>
              </div>
          </div>
          <div class="skip-btn">SKIP</div>
        </div>
    </div>
    <div class="tour-step first-step">
        <div class="terms-block modal-box">
            <div class="modal-body nano">
                <div class="nano-content">
                    <?php require('terms.php') ?>
                </div>
            </div>
            <div class="modal-footer">
                <a id="close-terms" class="sabres-admin-btn" href="#">Close</a>
            </div>
        </div>
        <div class="tour-block first">
            <img class="icon" src="<?php echo plugins_url('images/logo-light.png', __DIR__); ?>" alt="Sabres" title="Sabres">
        </div>
        <div class="tour-block second">
            <h2>Welcome to sabres security</h2>
            <p>This way you can get access to our portal for all information and settings you will ever need</p>
            <div class="tour-email">
                <input id="email-input" type="email" placeholder="Enter your email here">
            </div>
            <div class="email-opts">
                <div id="tfa-opt" class="tour-checkbox">
                    <label class="round-switch">
                        <input type="checkbox" id="tfa" name="tfa">
                        <span class="round-box"></span>
                    </label>
                    <label for="tfa">Use email for 2 step verification</label>
                </div>
                <div id="scan-opt" class="tour-checkbox">
                    <label class="round-switch">
                        <input type="checkbox" id="initial-scan" name="initial-scan">
                        <span class="round-box"></span>
                    </label>
                    <label for="initial-scan">Commit an initial scan</label>
                </div>
                <div id="terms-opt" class="tour-checkbox">
                    <label class="round-switch">
                        <input type="checkbox" id="terms" name="terms">
                        <span class="round-box"></span>
                    </label>
                    <label for="terms">Agree to <a id="terms-link" href="#">terms and conditions</a></label>
                </div>
            </div>
        </div>
        <div class="tour-block third" id="verification-block" hidden>
            <input class="tour-verification" id="verification-field" placeholder="Enter verification code"><br>
            <span>Didn't receive your verification mail? Check the email you entered</span>
        </div>
        <div class="tour-block fourth" id="register-block">
            <button class="sabres-admin-btn" id="email-btn">Register</button>
            <br>
            <span>Once you register you will receive a confirmation E-mail with a verification code</span>
        </div>
        <div class="progress-container">
          <div class="progress">
              <div class="progress-btn back disabled">
                  <span>Back</span>
                  <span class="progress-arrow"></span>
              </div>
              <div class="progress-indicator">
                  <span class="progress-circle active"></span>
                  <span class="progress-circle"></span>
                  <span class="progress-circle"></span>
                  <span class="progress-circle"></span>
                  <span class="progress-circle"></span>
              </div>
              <div class="progress-btn next disabled" id="send-email-opts">
                  <span class="progress-arrow"></span>
                  <span>Next</span>
              </div>
          </div>
          <div class="skip-btn">SKIP</div>
        </div>
    </div>
    <div class="tour-step activation-step">
        <div class="hint-container first-hint borderless"></div>
        <div class="tour-block first">
            <img src="<?php echo plugins_url('images/tour-arrows/page0-activation.png', __DIR__); ?>">
            <h3>It seems you are not online yet</h3>
            <p>Please reset activation</p>
        </div>
    </div>    
</div>
