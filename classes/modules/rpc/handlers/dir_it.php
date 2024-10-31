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

require_once( __DIR__ . '/../../../helpers/fail.php' );
require_once( __DIR__ . '/../../../helpers/regex-filters.php' );
require_once( __DIR__ . '/../../../helpers/filter-iterator/RecursiveCallbackFilterIterator.php' );

class SBRS_RPC_Dir_It {

    private $allowed_extensions = array(
        'php',
        'js',
        'htm',
        'html'
    );

    public function __construct()
    {
        $this->base_path = str_replace('\\', '/', ABSPATH);
    }

    public function execute() {
        return $this->get_core_files_list();
    }

    public function get_core_files_list() {
        $allowed_extensions = $this->allowed_extensions;

        $it = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator( ABSPATH, FilesystemIterator::SKIP_DOTS ), function ($fileInfo, $key, $iterator) use ($allowed_extensions) {

                $relative_path = trim( str_replace( realpath( ABSPATH ), '', dirname( $fileInfo->getRealPath() ) ), '/\\' );

                if ( !is_dir( $key ) ) {
                    $file_name = $fileInfo->getFilename();
                    $file_ext = pathinfo( $file_name, PATHINFO_EXTENSION );

                    if ( !in_array( $file_ext, $allowed_extensions) ) {
                        return false;
                    }
                }

                $result=( $relative_path == '' || stripos( $relative_path, 'wp-' ) === 0 );


                return $result;
            }
            )
        );


        $arr = array();
        foreach ($it as $fileinfo) {
            $arr[] = $this->get_file_info($fileinfo);
        }

        echo json_encode($arr);
    }

    private function get_file_info($fileinfo)
    {
        $result=array(
            'fn'=>$this->get_relative_path((string) $fileinfo),
            'ac'=>$this->get_date_time($fileinfo->getATime()),
            'md'=>$this->get_date_time($fileinfo->getMTime()),
            'cg'=>$this->get_date_time($fileinfo->getCTime()),
            'gid'=>$fileinfo->getGroup(),
            'uid'=>$fileinfo->getOwner(),
            'pr'=>decoct($fileinfo->getPerms()),
            'sz'=>$fileinfo->getSize(),
            'ex'=>(int) $fileinfo->isExecutable()
        );
        return $result;
    }

    private function get_relative_path($path)
    {
        return str_replace(array('\\',$this->base_path), array('/', ''), $path);
    }

    private function get_date_time($unix_time)
    {
        return date("Y-m-d H:i:s", $unix_time);
    }
}
