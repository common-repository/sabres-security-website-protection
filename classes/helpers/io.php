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

require_once( __DIR__ . '/error.php');
require_once( __DIR__ . '/parser.php');

abstract class SBRS_Helper_IO
{

    public static function get_files($paths = array(), $recursive = null, $exclude = array())
    {
        $ret = array();

        foreach ($paths as $path) {
            $folders = glob(realpath($path) . "/*", GLOB_ONLYDIR);
            $files = glob(realpath($path) . "/*", GLOB_BRACE);

            foreach ($files as $file) {
                if (!in_array($file, $exclude)) {
                    $ret[] = $file;
                }
            }

            if ($recursive) {
                foreach ($folders as $folder) {
                    $folder_files = self::get_files(array($folder), $recursive);
                    $ret = array_merge($ret, $folder_files);
                }
            }
        }

        return $ret;
    }

    public static function get_folders($paths = array(), $recursive = null, $exclude = array())
    {
        $ret = array();

        foreach ($paths as $path) {
            if (in_array($path, $exclude)) continue;

            $folders = glob(realpath($path) . "/*", GLOB_ONLYDIR);

            foreach ($folders as $folder) {
                $ret[] = $folder;

                if ($recursive) {
                    $subfolders = self::get_folders(array($folder), $recursive);
                    $ret = array_merge($ret, $subfolders);
                }
            }
        }

        return $ret;
    }

    public static function copy_folder($dir_path, $target_path)
    {
        if (empty($dir_path))
            SBRS_Helper_Error::throwError('Folder path cannot remain empty');

        if (empty($target_path))
            SBRS_Helper_Error::throwError('Target path cannot remain empty');

        if (!is_dir($dir_path))
            SBRS_Helper_Error::throwError(sprintf('Source directory not found: %s', $dir_path));

        if (!is_dir($target_path))
            SBRS_Helper_Error::throwError(sprintf('Target directory not found: %s', $target_path));

        if (!is_writable($target_path))
            @chmod($target_path, 0755);

        if (!is_writable(dirname($target_path)))
            SBRS_Helper_Error::throwError(sprintf('Can\'t copy to directory: %s Check your permissions!', $target_path));

        self::copy_folder_recursive($dir_path, $target_path);
    }

    private static function copy_folder_recursive($dir_path, $target_path)
    {
        $dir = opendir($dir_path);

        if (!is_dir($target_path))
            mkdir($target_path);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($dir_path . '/' . $file)) {
                    self::copy_folder_recursive($dir_path . '/' . $file, $target_path . '/' . $file);
                } else {
                    copy($dir_path . '/' . $file, $target_path . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    public static function delete_folder($dir)
    {
        if (is_dir($dir)) {
            $files = scandir($dir);

            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    if (is_dir($dir . "/" . $file))
                        self::delete_folder($dir . "/" . $file);
                    else
                        unlink($dir . "/" . $file);
                }
            }

            rmdir($dir);
        }
    }

    public function recur_dir_it($root, $max_depth)
    {
        $dir = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, folders come before their files
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);

        // Maximum depth is 1 level deeper than the base folder
        $it->setMaxDepth($max_depth);

        $arr = array();
        foreach ($it as $fileinfo) {

            $arr[] = $this->get_file_info($fileinfo);
        }
        return $arr;
    }

    public static function read_file($file_path)
    {
        if (!is_file($file_path)) {
            throw new \Exception("File not found: $file_path");
        }

        if (!$file_content = file_get_contents($file_path)) {
            throw new \Exception("Cannot read file due to insufficent permissions: $file_path");
        }

        return $file_content;
    }

    public static function write_file($file_path, $content)
    {
        if (!@file_put_contents($file_path, $content)) {
            throw new \Exception("Cannot write to file due to insufficent permissions: $file_path");
        }

        return true;
    }

    public static function patch_file($file_path, $function_name, $function_args, $leading_arg = null)
    {
        $new_content = null;

        $file_content = self::read_file($file_path);
        $functions = SBRS_Helper_Parser::get_functions($file_content, $function_name, $leading_arg);

        if (!empty($functions[0])) {
            if (isset($leading_arg)) {
                for ($i = 0; $i < count($functions); $i++) {
                    list($content, $offset) = $functions[$i];

//                        if ( $i > 0 && strcasecmp( $function_name, 'define' ) == 0 ) {
//                            $new_content = substr_replace( $file_content, $function_args, $offset, strlen( $content ) );
//                        } else {
                    $new_content = substr_replace($file_content, $function_args, $offset, strlen($content));
                    //}

                    if (self::write_file($file_path, $new_content)) {
                        $file_content = self::read_file($file_path);
                        $functions = SBRS_Helper_Parser::get_functions($file_content, $function_name, $leading_arg);
                    }
                }
            } else {
                list($contents, $args) = $functions;

                if (!empty($contents)) {
                    for ($i = 0; $i < count($contents); $i++) {
                        list($content, $offset) = $contents[$i];

                        $new_content = substr_replace($file_content, "$function_name($function_args);", $offset, strlen($content));

                        if (self::write_file($file_path, $new_content)) {
                            $file_content = self::read_file($file_path);
                            $functions = SBRS_Helper_Parser::get_functions($file_content, $function_name, $leading_arg);

                            list($contents, $args) = $functions;
                        }
                    }
                }
            }
        } else {
            $new_content = "<?php $function_name($function_args); ?>\n$file_content";

            self::write_file($file_path, $new_content);
        }


        return true;
    }

    public
    static function _patch_file($file_path, $function_name, $function_args)
    {
        $new_content = null;

        $file_content = self::read_file($file_path);
        $first_arg = current(explode(',', $function_args));
        $current_args = SBRS_Helper_Parser::get_functions($file_content, $function_name, $first_arg);
        //$functions = SBS_Parser::get_functions( $file_content, $function_name );

        if (!empty($current_args)) {
            list($arg_content, $arg_offset) = $current_args[0];

            $new_content = substr_replace($file_content, $function_args, $arg_offset, strlen($arg_content));
        } else {
            $new_content = "<?php $function_name($function_args); ?>\n$file_content";
        }

        if (!@file_put_contents($file_path, $new_content)) {
            throw new \Exception("Cannot write to file due to insufficent permissions: $file_path");

            return false;
        }

        return true;
    }

}
