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

require_once( __DIR__ . '/ihandler.php' );
require_once( __DIR__ . '/../../logger.php' );
require_once( __DIR__ . '/../../request.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );

class SBRS_Lifecycle_WriteLog implements SBRS_Lifecycle_IHandler
{
    /** @var  SBRS_Logger */
    private $logger;
    /** @var  SBRS_Request */
    private $request;
    /** @var  SBRS_Settings */
    private $settings;
    /** @var  SBRS_WP */
    private $wp;

    public function __construct($request, $settings, $wp, $logger)
    {
        $this->request = $request;
        $this->settings = $settings;
        $this->wp = $wp;
        $this->logger = $logger;
    }

    public function write($data)
    {
        $this->logger->log('info', 'lifecycle', null, $data);
    }



}