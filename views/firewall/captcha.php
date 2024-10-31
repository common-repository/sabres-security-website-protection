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

<html>
<head>
    <link href="<?php echo '/wp-content/plugins/sabres/css/style.css'; ?>" rel="stylesheet">
</head>
<body>
<div class="sbr-captcha">
    <div class="sbr-captcha-panel">
        <div class="sbr-captcha-title">
            A minor step to keep our website safe
        </div>
        <div class="sbr-captcha-container">
            <div class="sbr-captcha-img"><img src="<?php echo $captcha_data ?>"/></div>
            <div class="sbr-captcha-arrow"></div>
        </div>

        <div class="sbr-captcha-controls">
            <div class="sbr-captcha-label">
                Type the word above
            </div>
            <div class="sbr-captcha-inputs">
                <form method="post">
                    <input type="text" name="sbs_firewall_captcha_phrase" maxlength="6" class="sbr-captcha-input"/>
                    <button class="sbr-captcha-refresh-button" name="sbs_firewall_captcha_refresh"></button>
                    <button class="sbr-captcha-submit-button">Submit</button>
                </form>
            </div>
        </div>
    </div>
    <div class="sbr-captcha-footer">
        This website is protected by Sabres Security
    </div>
</div>
<?php $traffic_dispatcher->write_client_script('C'); ?>
</body>
</html>