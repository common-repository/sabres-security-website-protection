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

if (!class_exists('CallbackFilterIterator')) {

    class CallbackFilterIterator extends FilterIterator
    {

        private $iterator;
        private $callback;

        public function __construct(Iterator $iterator, $callback)
        {
            $this->iterator = $iterator;
            $this->callback = $callback;
            parent::__construct($iterator);
        }

        public function accept()
        {
            return call_user_func($this->callback, $this->current(), $this->key(), $this->iterator);
        }

    }

}
