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

<script src="<?php echo plugins_url('javascript/jquery.sabres.js', __DIR__); ?>"></script>
<script src="<?php echo plugins_url('javascript/sabres-issues.js', __DIR__); ?>"></script>

<?php
$this->write_admin_data();
require('tour.php');
?>

<div class="container-fluid sabres-issues">
    <?php require('header.php'); ?>

    <div class="row">
        <div class="col-lg-10 col-md-9 col-sm-8">
            <div class="panel panel-settings">
                <div class="panel-heading">
                    <span>Issues</span>
                </div>
                <div class="panel-body">
                    <table class="data-table data-table-full-width issues">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Risk</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="issues-item">
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-3 col-sm-4">
            <?php require('banners.php') ?>
        </div>
    </div>
</div>