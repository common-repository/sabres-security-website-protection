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

require_once( __DIR__ . '/scanner-engine.php' );
require_once( __DIR__ . '/scanner-engine-factory.php' );
require_once( __DIR__ . '/../../helpers/server.php' );

class SBRS_Scanner
{
    private $scan_type;
    private $settings;

    /** @var  SBRS_Helper_Server */
    private $server;
    /** @var  SBRS_Scanner_Engine_Factory */
    private $factory;

    private $engine;

    private $current_scan;

    protected static $instance;


    public function __construct(SBRS_Helper_Server $server, SBRS_Scanner_Engine_Factory $factory)
    {
        $this->server = $server;
        $this->factory = $factory;
    }


    public function init($settings = null, $scan_type)
    {
        $this->settings = (object)array_change_key_case($settings, CASE_LOWER);
        $this->scan_type = $scan_type;
    }

    private function init_engine()
    {

        $this->engine = $this->factory->create($this->scan_type, $this->current_scan);

    }

    public function run()
    {
        // Disable time limit
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        // Disable memory limit
        @ini_set('memory_limit', '-1');

        $this->current_scan = $this->find_scan($this->scan_type);
        if (empty($this->current_scan)) {
            $this->current_scan = $this->add_scan();
        } else {
            $this->clear_scan($this->current_scan->ID);
        }

        $this->current_scan->items = array();

        $this->init_engine();

        if (!isset($this->engine)) return;

        $self = $this;
        $this->engine->register_event_callback('status', function ($status) use ($self) {
            $self->set_status($status);
        });

        $this->engine->register_event_callback('info', function ($desc) use ($self) {
            $self->add_scan_item('info', null, $desc);
        });

        $this->engine->register_event_callback('summary', function ($desc) use ($self) {
            $self->add_scan_item('summary', null, $desc);
        });

        $this->engine->register_event_callback('error', function ($desc) use ($self) {
            $self->add_scan_item('error', null, $desc);
        });

        $this->engine->register_event_callback('issue', function ($risk_level, $code, $desc, $unique_id) use ($self) {
            $self->add_scan_item('issue', $code, $desc, $risk_level, $unique_id);
        });

        $this->engine->start();
    }

    public function fix_issue($code, $unique_id)
    {
        if (!isset($this->engine)) $this->init_engine();

        if (!isset($this->engine)) return false;

        return $this->engine->fix_issue($code, $unique_id);
    }

    public function get_current_scan()
    {
        return $this->current_scan;
    }

    public function clear_scans()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scan_items';
        $sql = "DELETE FROM $table_name";
        $wpdb->query($sql);

        $table_name = $wpdb->prefix . 'sbs_scans';
        $sql = "DELETE FROM $table_name";
        $wpdb->query($sql);

        return true;
    }

    public function clear_scan($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scan_items';
        $sql = "DELETE FROM $table_name WHERE $table_name.parent_id = %d";

        return $wpdb->query($wpdb->prepare($sql, $id));
    }

    public function set_status($status)
    {
        if (isset($this->current_scan)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sbs_scans';

            $sql = "UPDATE $table_name SET $table_name.status = '%s', $table_name.updated_at = now() WHERE ID = %d";

            return $wpdb->query($wpdb->prepare($sql, $status, $this->current_scan->ID));
        }
    }

    public function get_scans_by_status($status)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scans';

        $query = "SELECT * FROM $table_name WHERE $table_name.status = '$status'";

        return $wpdb->get_results($query, OBJECT);
    }

    public function get_scan($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scans';

        $query = "SELECT * FROM $table_name WHERE $table_name.ID = $id";

        return $wpdb->get_row($query, OBJECT);
    }

    public function find_scan($code)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scans';

        $query = "SELECT * FROM $table_name WHERE $table_name.scan_type = '$code'";

        return $wpdb->get_row($query, OBJECT);
    }

    public function get_scan_item($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scan_items';

        $query = "SELECT * FROM $table_name WHERE $table_name.ID = $id";

        return $wpdb->get_row($query, OBJECT);
    }

    public function add_scan()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbs_scans';

        $sql = "INSERT INTO $table_name ( $table_name.scan_type, $table_name.status) VALUES (%s, %s)";

        if ($wpdb->query($wpdb->prepare($sql, $this->scan_type, 'idle'))) {
            return $this->get_scan($wpdb->insert_id);
        }
    }

    public function add_scan_item($type, $code = null, $desc, $risk_level = null, $unique_id = null)
    {
        if (isset($this->current_scan)) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'sbs_scan_items';

            $sql = "INSERT INTO $table_name ( $table_name.parent_id, $table_name.item_type, $table_name.item_code, $table_name.item_desc, $table_name.risk_level, $table_name.unique_id) VALUES (%d, %s, %s, %s, %d, %s)";

            if ($wpdb->query($wpdb->prepare($sql, $this->current_scan->ID, $type, $code, $desc, $risk_level, $unique_id))) {
                $item = $this->get_scan_item($wpdb->insert_id);

                $this->current_scan->items[] = $item;

                return $item;
            }
        }

    }


}
