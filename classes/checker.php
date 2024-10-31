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

class SBRS_Checker
{
    protected $errors = array();

    public function test($tests = array())
    {
        $this->errors = array();
        foreach ($tests as $test => $args) {
            if (method_exists($this, $test)) {
                $this->$test($args);
            } else {
                $this->errors['checker'] = 'Test ' . $test . ' not exist';
            }
        }
        if (count($this->errors)) {
            return $this->errors;
        }
        return true;
    }


    //do not remove
    public function example_test($args)
    {
        if (true) {
            return true;
        } else {
            $this->errors['example_test'] = $args;
            return false;
        }
    }

    public function check_php_functions($args)
    {
        $errors = array();
        $args = is_array($args) ? $args : array($args);
        foreach ($args as $phpfunction) {
            if (!function_exists($phpfunction)) {
                $errors[] = $phpfunction;
            }
        }
        if (count($errors) != 0) {
            $this->errors['check_php_functions'] = $errors;
            return false;
        }
        return true;

    }


}
