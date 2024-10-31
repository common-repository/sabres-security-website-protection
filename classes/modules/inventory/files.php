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

require_once( __DIR__ . '/../../wp.php' );

class SBRS_Inventory_Files
{

    protected $text_exts = array(
        0 => 'appcache',
        1 => 'ics',
        2 => 'ifb',
        3 => 'css',
        4 => 'csv',
        5 => 'html',
        6 => 'htm',
        7 => 'n3',
        8 => 'txt',
        9 => 'text',
        10 => 'conf',
        11 => 'def',
        12 => 'list',
        13 => 'log',
        14 => 'in',
        15 => 'dsc',
        16 => 'rtx',
        17 => 'sgml',
        18 => 'sgm',
        19 => 'tsv',
        20 => 't',
        21 => 'tr',
        22 => 'roff',
        23 => 'man',
        24 => 'me',
        25 => 'ms',
        26 => 'ttl',
        27 => 'uri',
        28 => 'uris',
        29 => 'urls',
        30 => 'vcard',
        31 => 'curl',
        32 => 'dcurl',
        33 => 'mcurl',
        34 => 'scurl',
        35 => 'sub',
        36 => 'fly',
        37 => 'flx',
        38 => 'gv',
        39 => '3dml',
        40 => 'spot',
        41 => 'jad',
        42 => 'wml',
        43 => 'wmls',
        44 => 's',
        45 => 'asm',
        46 => 'c',
        47 => 'cc',
        48 => 'cxx',
        49 => 'cpp',
        50 => 'h',
        51 => 'hh',
        52 => 'dic',
        53 => 'f',
        54 => 'for',
        55 => 'f77',
        56 => 'f90',
        57 => 'java',
        58 => 'nfo',
        59 => 'opml',
        60 => 'p',
        61 => 'pas',
        62 => 'etx',
        63 => 'sfv',
        64 => 'uu',
        65 => 'vcs',
        66 => 'vcf',
        67 => 'php',
        68 => 'js',
        69 => 'md',
        70 => 'py',
        71 => 'ini',
        72 => 'po',
        73 => 'svg',
    );

    /** @var  SBRS_WP */
    private $wp;

    public function __construct($wp) {
        $this->wp = $wp;
    }

    public function validate_inventory_can_execute()
    {
        if (function_exists('pathinfo'))
            return;
        if (!function_exists('finfo_open'))
            $this->wp->wp_real_die('{"error":"finfo_open and pathinfo function does not exist"}');
        if (!function_exists('finfo_file'))
            $this->wp->wp_real_die('{"error":"finfo_file and pathinfo function does not exist"}');
    }

    public function get_files($path, $extensions = array(), $recursive = null)
    {
        $ret = array();

        $folders = glob(realpath($path) . "/*", GLOB_ONLYDIR | GLOB_NODOTS);
        if (count($extensions)) {
            $files = array();
            foreach ($extensions as $ext) {
                $files = array_merge($files, glob(realpath($path) . "/*" . $ext, GLOB_BRACE));
            }
        } else {
            $files = glob(realpath($path) . "/*", GLOB_BRACE);
        }


        foreach ($files as $file) {
            $ret[] = $file;
        }

        if ($recursive) {
            foreach ($folders as $folder) {
                $folder_files = $this->get_files($folder, $extensions, $recursive);
                $ret = array_merge($ret, $folder_files);
            }
        }

        return $ret;
    }

    public function get_dirs($path)
    {
        return glob(realpath($path) . "/*", GLOB_ONLYDIR);
    }

    public function exclude_no_readable($files)
    {
        $result = array();
        $excluded = array();

        foreach ($files as $key => $file) {
            if (is_readable($file)) {
                $result[$key] = $file;
            } else {
                $excluded[$key] = $file;
            }
        }

        return array($result, $excluded);
    }

    public function calc_md5_file($filename, &$file_data)
    {
        if ($this->is_text_file($filename)) {
            $file_data['ht'] = 'T';
            $content = file_get_contents($filename);
            $content = preg_replace('/\s+/', '', $content);
            $file_data['signature'] = md5($content);
            return;
        }
        $file_data['ht'] = 'B';
        $file_data['signature'] = md5_file($filename);
    }


    public function is_text_file_by_mime_type($filename)
    {
        $finfo = finfo_open(FILEINFO_MIME);
        //check to see if the mime-type starts with 'text'
        return substr(finfo_file($finfo, $filename), 0, 4) == 'text';
    }


    public function is_text_file_by_ext($filename)
    {

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (empty($ext) || in_array($ext, $this->text_exts))
            return true;
        return false;
    }

    public function is_text_file($filename)
    {
        if ($this->is_text_file_by_ext($filename))
            return true;


        if (function_exists('finfo_open') && function_exists('finfo_file'))
            return $this->is_text_file_by_mime_type($filename);
        return false;
    }

    public static function find_relative_path($frompath, $topath)
    {
        $from = explode(DIRECTORY_SEPARATOR, $frompath); // Folders/File
        $to = explode(DIRECTORY_SEPARATOR, $topath); // Folders/File
        $relpath = '';

        $i = 0;
        // Find how far the path is the same
        while (isset($from[$i]) && isset($to[$i])) {
            if ($from[$i] != $to[$i]) break;
            $i++;
        }
        $j = count($from) - 1;
        // Add '..' until the path is the same
        while ($i <= $j) {
            if (!empty($from[$j])) $relpath .= '..' . DIRECTORY_SEPARATOR;
            $j--;
        }
        // Go to folder from where it starts differing
        while (isset($to[$i])) {
            if (!empty($to[$i])) $relpath .= $to[$i] . DIRECTORY_SEPARATOR;
            $i++;
        }

        // Strip last separator
        return substr($relpath, 0, -1);
    }
}