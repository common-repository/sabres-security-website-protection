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

class SBRS_Helper_Fail {

  public static function bye($message,$logData=null,$includeBacktrace=true,$code=null) {
    if (!is_null($code)) {
      if ( !headers_sent() ) {
          foreach ( headers_list() as $header ) {
              @header_remove( $header );
          }
      }      
      @header( 'X-PHP-Response-Code', true, $code );

    }
    $result=array();
    $result['error']=$message;
    if (isset($logData))
      $result['data']=$logData;
    if ($includeBacktrace) {
      $result['backtrace']=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }
    die(json_encode($result));

  }

  public static function byeArr($args) {
    $message=@$args['message'];
    $logData=@$args['logData'];
    $includeBacktrace=@$args['includeBacktrace'];
    $code=@$args['code'];
    self::bye($message,$logData,$includeBacktrace,$code);

  }

}
