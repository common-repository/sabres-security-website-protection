<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
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


require_once( __DIR__ . '/../../vendor/gregwar/captcha/autoload.php');
require_once( __DIR__ . '/../../vendor/gregwar/captcha/CaptchaBuilder.php');

abstract class SBRS_Helper_Captcha
{

    const WIDTH = 330;

    const HEIGHT = 80;

    public static function get_captcha($keyphrase = null)
    {
        $captcha = '';
        if (function_exists('imagettfbbox')) {
            if (class_exists('Gregwar\Captcha\CaptchaBuilder')) {
                $builder = new Gregwar\Captcha\CaptchaBuilder($keyphrase);
                $builder->setMaxBehindLines(2);
                $builder->setMaxFrontLines(0);
                $builder->setBackgroundColor(255, 255, 255);
                $builder->build(static::WIDTH, static::HEIGHT);
                $keyphrase = $builder->getPhrase();
                $captcha = $builder->inline(100);
            }
        } else {
            @include_once(__DIR__ . '/purecaptcha/purecaptcha.php');

            if (class_exists('SBRS_Pure_Captcha')) {
                $pure_captcha = new \SBRS_Pure_Captcha();

                if (!is_null($keyphrase))
                    $pure_captcha->setText($keyphrase);

                $pure_captcha_data = $pure_captcha->get();

                if (!empty($pure_captcha_data)) {
                    $keyphrase = $pure_captcha_data['keyphrase'];
                    $captcha = 'data:image/bmp;base64,' . base64_encode($pure_captcha_data['captcha']);
                }
            } else {
                die('Human verification cannot be generated.');
            }
        }
        if (!empty($keyphrase) && !empty($captcha)) {
            return array(
                'keyphrase' => $keyphrase,
                'captcha' => $captcha
            );
        }
    }
}
