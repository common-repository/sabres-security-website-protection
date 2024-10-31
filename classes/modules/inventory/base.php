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

require_once( __DIR__ . '/files.php' );
require_once( __DIR__ . '/../../logger.php' );
require_once( __DIR__ . '/../../wp.php' );

abstract class SBRS_Inventory_Base {
	/** @var  SBRS_WP */
	protected $wp;
	/** @var  SBRS_Logger */
	protected $logger;
	/** @var  SBRS_Inventory_Files */
	protected $inventory_files;

	public function __construct(SBRS_WP $wp, SBRS_Logger $logger, SBRS_Inventory_Files $inventory_files)
	{
		$this->wp = $wp;
		$this->logger = $logger;
		$this->inventory_files = $inventory_files;
	}

	public function can_execute() {
		$this->inventory_files->validate_inventory_can_execute();
	}

	public abstract function get_inventory($files = null, $exts);
}