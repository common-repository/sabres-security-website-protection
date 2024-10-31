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

require_once( __DIR__ . '/callback-parser.php' );
require_once( __DIR__ . '/ifactory-handler.php' );
require_once( __DIR__ . '/message-content-provider.php' );
require_once( __DIR__ . '/../../settings.php' );
require_once( __DIR__ . '/../../wp.php' );
require_once( __DIR__ . '/../../helpers/crypto.php' );
require_once( __DIR__ . '/../../helpers/fail.php' );

Class SBRS_RPC_Dispatcher
{

    /** @var SBRS_RPC_Callback_Parser */
    private $argsParserCallback;
    /** @var SBRS_RPC_Message_Content_Provider */
    private $messageContentProvider;
    private $outputFinalized = false;
    private $parsedMessage = null;
    /** @var SBRS_RPC_IFactory_Handler */
    private $factoryHandler;
    /** @var  SBRS_Settings */
    private $settings;
    /** @var  SBRS_WP */
    private $wp;

    function __construct(SBRS_WP $wp, $settings, SBRS_RPC_Callback_Parser $argsParserCallback, SBRS_RPC_Message_Content_Provider $messageContentProvider, SBRS_RPC_IFactory_Handler $factoryHandler)
    {
        $this->wp = $wp;
        $this->settings = $settings;
        $this->argsParserCallback = $argsParserCallback;
        $this->messageContentProvider = $messageContentProvider;
        $this->factoryHandler = $factoryHandler;
    }

    public function dispatch()
    {
        @ob_clean();
        ob_start();

        $ip = $_SERVER['REMOTE_ADDR'];
        $authorized_IPs = $this->settings->authorized_RPC_IPs;

        if (!in_array($ip, $authorized_IPs) && strtolower($this->settings->block_unauthorized_RPC_IPs) == 'true') {
            $this->denyAccess($ip);
        } else {
            $this->getParsedMessage();
            $this->handleParsedMessage();
        }

        $this->finalizeOutput();

    }

    public function denyAccess($ip)
    {
        echo "start sabres rpc output" . PHP_EOL;
        echo "Access denied from non authorized IP " . $ip . PHP_EOL;
    }

    public function finalizeOutput()
    {
        //echo "Shutting Down";
        if (!$this->outputFinalized) {
            $this->outputFinalized = true;

            $output = ob_get_contents() . PHP_EOL . "end sabres rpc output";
            @ob_end_clean();
            if (!$this->unencryptedResponse) {

                $settings = $this->settings;

                if (strlen($settings->symmetricEncryptionKey) == 40) {
                    $aes_key = substr($settings->symmetricEncryptionKey, 0, 32);
                    $aes_iv = substr($settings->symmetricEncryptionKey, 24, 16);
                    $output = SBRS_Helper_Crypto::encrypt($aes_key, $aes_iv, $output);
                }
            }
            echo $output;
        }
        $this->wp->wp_real_die();
    }

    public function onShutDown()
    {
        $this->finalizeOutput();
    }

    private $unencryptedResponse = false;


    public function getParsedMessage()
    {
        if (!extension_loaded('openssl'))
            SBRS_Helper_Fail::bye("Open SSL extension is not loaded");
        $args = $this->argsParserCallback->parseRequest();
        if (!isset($args))
            return;

        echo "start sabres rpc output" . PHP_EOL;


        $messageContent = $this->messageContentProvider->getMessageContent($args["message-id"]);
        $parsedMessage = $this->argsParserCallback->parse_message($messageContent);

        if (!isset($parsedMessage))
            SBRS_Helper_Fail::bye("Failed to parse message: " . $messageContent);
        if (!isset($parsedMessage['op']))
            SBRS_Helper_Fail::bye("Missing mandatory parameter op. message: " . $messageContent);
        if (isset($parsedMessage['unencryptedResponse']) && strcasecmp($parsedMessage['unencryptedResponse'], true))
            $this->unencryptedResponse = true;
        $this->parsedMessage = $parsedMessage;
        return $parsedMessage;

    }

    public function handleParsedMessage()
    {
        $parsedMessage = $this->parsedMessage;

        $handler = $this->factoryHandler->createHandler($parsedMessage['op']);
        if (!isset($handler))
            SBRS_Helper_Fail::byeArr(array('message' => "Unkown op: " . $parsedMessage['op'],
                'code' => 400
            ));

        $handler->execute($parsedMessage);

    }


}
