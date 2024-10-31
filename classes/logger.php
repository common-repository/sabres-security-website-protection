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

require_once( __DIR__ . '/plugin.php');
require_once( __DIR__ . '/helpers/fail.php');
require_once( __DIR__ . '/helpers/utils.php');

class SBRS_Logger
{

    /** @var  SBRS_WP */
    private $wp;

    const LOG_TABLE_NAME = 'sbs_log';

    protected static $instance;

    private static $table_fields;


    public function __construct(SBRS_WP $wp)
    {
        $this->wp = $wp;
        SBRS_Plugin::upgradeLoggerDb();
    }


    private static function get_table_fields()
    {
        if (empty(self::$table_fields))
            self::$table_fields = array(
                'ID' => array('type' => 'int'),
                'log_type' => array('type' => 'string'),
                'logger' => array('type' => 'string'),
                'message' => array('type' => 'string'),
                'log_data' => array('type' => 'string'),
                'created_at' => array('type' => 'datetime'));
        return self::$table_fields;
    }

    private static $string_ops;

    private static function get_string_ops()
    {
        if (empty(self::$string_ops))
            self::$string_ops = array('LIKE');
        return self::$string_ops;
    }

    private static $numeric_ops;

    private static function get_numeric_ops()
    {
        if (empty(self::$numeric_ops))
            self::$numeric_ops = array('eq', 'gt', 'gte', 'lt', 'lte');
        return self::$numeric_ops;
    }


    public function log($type, $logger, $message, $data = null, $backtrace = false)
    {
        $table_name = $this->wp->get_prefix() . self::LOG_TABLE_NAME;

        $sql = "INSERT INTO $table_name ( $table_name.log_type, $table_name.logger, $table_name.message, $table_name.log_data, $table_name.backtrace) VALUES (%s, %s, %s, %s, %s)";
        return $this->wp->query($this->wp->prepare($sql, $type, $logger, $message, (isset($data) ? json_encode($data, JSON_FORCE_OBJECT) : null), ($backtrace ? $this->getBacktrace() : null)));


    }

    public function get_entries($start = null, $end = null)
    {

        $table_name = $this->wp->get_prefix() . self::LOG_TABLE_NAME;

        $sql = "SELECT * FROM $table_name";

        if (!empty($start) && !empty($end)) {
            $sql .= " WHERE $table_name.created_at >= '%s' AND $table_name.created_at < '%s'";

            $res = $this->wp->get_results($this->wp->prepare($sql, $start, $end), OBJECT);
        } else {
            $res = $this->wp->get_results($sql, OBJECT);
        }

        if (!empty($res)) {
            foreach ($res as &$entry) {
                if (!empty($entry->log_data)) {
                    $entry->log_data = json_decode($entry->log_data);
                }
            }
        }

        return $res;
    }

    private function fail($message)
    {
        SBRS_Helper_Fail::byeArr(array('message' => $message,
            'code' => 500,
            'includeBacktrace' => false
        ));
    }

    public function get_entries_new($args)
    {

        $table_name = $this->wp->get_prefix() . self::LOG_TABLE_NAME;
        $values = array();

        if (!isset($args['distinct']))
            $distinct = '';
        else if (strcasecmp($args['distinct'], 'true') != 0)
            $distinct = '';
        else
            $distinct = 'DISTINCT ';

        if (!isset($args['fields']))
            $select_fields = '*';
        else if ($args['fields'] = '*')
            $select_fields = '*';
        else {
            $select_fields = $args['fields'];
            $select_fields_arr = explode(',', $select_fields);
            foreach ($select_fields_arr as $select_field) {

                $field_data = @self::get_table_fields();
                $field_data = @$field_data[$select_field];
                if (empty($field_data))
                    $this->fail('Invalid selected field value ' . var_export($select_field, true));
            }
        }

        $sql = "SELECT $distinct $select_fields FROM $table_name ";


        $body = @$args['body'];
        if (!empty($body)) {
            $body = urldecode($body);
            $body = json_decode($body);
            if (empty($body))
                $this->fail('Invalid value for body ' . var_export($args['body'], true));
            $where = @$body->where;
            if (!empty($where)) {
                $reg_exp = '/\*\*([a-zA-Z0-9\-, _%]+?)\*\*/';
                preg_match_all($reg_exp, $where, $matches);
                $remainder = preg_replace($reg_exp, '', $where);
                $where = preg_replace($reg_exp, '%s', $where);
                $values = array_merge($values, $matches[1]);
                $remainder = preg_replace('/or|and|not|\(|\)|>|<|>=|<=|=|like/i', '', $remainder);
                $remainder = preg_replace('/' . implode('|', array_keys(self::get_table_fields())) . '/', '', $remainder);
                if (trim($remainder) != '') {
                    $this->fail('Illegal symbols in where clause, where:' . var_export(@$body->where, true) . " illegal: " . trim($remainder));
                }

                $sql .= "WHERE $where ";
            }
        }

        if (!isset($args['sort_dir']))
            $sort_dir = 'desc';
        else if (strcasecmp($args['sort_dir'], 'desc') == 0)
            $sort_dir = 'desc';
        else if (strcasecmp($args['sort_dir'], 'asc') == 0)
            $sort_dir = 'asc';

        $sql .= "ORDER BY ID $sort_dir ";

        if (!isset($args['limit']))
            $limit = 20;
        else if (!SBRS_Helper_Utils::is_integer($args['limit'], $limit))
            $this->fail('Invalid value for limit ' . var_export($args['limit'], true));
        if (!isset($args['offset']))
            $offset = 0;
        else if (!SBRS_Helper_Utils::is_integer($args['offset'], $offset))
            $this->fail('Invalid value for offset ' . var_export($args['offset'], true));
        $sql .= "LIMIT $limit OFFSET $offset";

        $query = count($values) > 0 ? $this->wp->prepare($sql, $values) : $sql;

        $res = $this->wp->get_results($query, OBJECT);

        if (!empty($res)) {
            foreach ($res as &$entry) {
                if (!empty($entry->log_data)) {
                    $entry->log_data = json_decode($entry->log_data);
                }
            }
        }

        return $res;
    }

    public function clear_entries($start = null, $end = null)
    {

        $table_name = $this->wp->get_prefix() . self::LOG_TABLE_NAME;

        $query = $sql = "DELETE FROM $table_name";
        $criteria = '';

        if (!empty($start)) {
            $criteria .= " WHERE $table_name.created_at >= '%s'";

            $query = $this->wp->prepare($sql . $criteria, $start);

        }

        if (!empty($end)) {
            if (empty($criteria)) {
                $criteria = ' WHERE ';
            } else {
                $criteria = ' AND ';
            }
            $criteria .= " $table_name.created_at < '%s'";

            $query = $this->wp->prepare($query . $criteria, $end);
        }

        return $this->wp->query($query);
    }

    public function getBacktrace()
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();
        return $trace;
    }

    public function get_last_activation_error() {
        $table_name = $this->wp->get_prefix() . self::LOG_TABLE_NAME;
        $sql = "SELECT log_data FROM $table_name WHERE logger = 'check_server_token' ORDER BY created_at ASC LIMIT 1";
        $results = $this->wp->get_results( $sql );

        if ( ! isset( $results[0] ) ) {
            return false;
        }

        $result = json_decode( $results[0]->log_data );

        if ( ! isset( $result->body ) ) {
            return false;
        }

        $body = json_decode( $result->body );

        if ( isset($body->error) ) {
            return $body->error;
        } else {
            return false;
        }
    }

}
