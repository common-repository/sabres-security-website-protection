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

require_once( __DIR__ . '/fail.php');

/**
 * The Sabres Crypto Class
 *
 * @author Sabres Security inc
 * @package Sabres_Security_Plugin
 * @since 1.0.0
 */
abstract class SBRS_Helper_Crypto
{

    public static function encrypt($key, $iv, $value)
    {
        if (function_exists('mcrypt_encrypt')) {
            $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $padding = $block - (strlen($value) % $block);
            $value .= str_repeat(chr($padding), $padding);

            return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $value, MCRYPT_MODE_CBC, $iv));
        } else {
            @include_once __DIR__ . '/phpcrypt-0.5.1/phpCrypt.php';

            if (class_exists('PHP_Crypt\PHP_Crypt')) {
                $crypt = new \PHP_Crypt\PHP_Crypt($key, \PHP_Crypt\PHP_Crypt::CIPHER_AES_256, \PHP_Crypt\PHP_Crypt::MODE_CBC, \PHP_Crypt\PHP_Crypt::PAD_PKCS7);

                $block = $crypt->cipherBlockSize();
                $padding = $block - (strlen($value) % $block);
                $value .= str_repeat(chr($padding), $padding);

                $crypt->IV($iv);

                return base64_encode($crypt->encrypt($value));
            } else {
                SBRS_Helper_Fail::bye('Encryption support is missing. Plugin is corrupt. Can no continue.');
            }
        }
    }

    public static function decrypt($key, $iv, $value)
    {
        if (function_exists('mcrypt_decrypt')) {
            return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($value), MCRYPT_MODE_CBC, $iv), "\0..\32");
        } else {
            require_once __DIR__ . '/phpcrypt-0.5.1/phpCrypt.php';

            if (class_exists('\PHP_Crypt\PHP_Crypt')) {
                $crypt = new \PHP_Crypt\PHP_Crypt($key, \PHP_Crypt\PHP_Crypt::CIPHER_AES_256, \PHP_Crypt\PHP_Crypt::MODE_CBC, \PHP_Crypt\PHP_Crypt::PAD_PKCS7);
                $crypt->IV($iv);

                return rtrim($crypt->decrypt(base64_decode($value), "\0..\32"));
            } else {
                SBRS_Helper_Fail::bye('Encryption support is missing. Plugin is corrupt. Can no continue.');
            }
        }
    }

    public static function get_random_hash()
    {
        //  list( $usec, $sec ) = explode( ' ', microtime() );

        //  srand( (float) $sec + ( (float) $usec * 100000 ) );

        //return sha1( microtime() + mt_rand() );

        $mt1 = mt_rand();
        $mt2 = mt_rand();
        $arg = strval($mt1) . strval($mt2);
        return sha1($arg);

    }
}

