<?php
/**
 * 苹果客户端推送类
 *
 * @author fengzbao@qq.com
 * @copyright Copyright (c) fzb.me
 * @version $Id:1.0.0, Push.php, 2015-08-28 16:14 created (updated)$
 */

namespace Push;


class Push
{
    /**
     *
     * < @type integer Production environment.
     */
    const ENVIRONMENT_PRODUCTION = 0;

    /**
     *
     * < @type integer Sandbox environment.
     */
    const ENVIRONMENT_SANDBOX = 1;

    /**
     *
     *
     * @var $environment int
     */
    private $environment;

    /**
     *
     *
     * @var array Service URLs environments.
     */
    protected $serviceURLs = array(
        'tls://gateway.push.apple.com:2195', // Production environment
        'tls://gateway.sandbox.push.apple.com:2195' // Sandbox environment
    );


    /**
     * certificate file path
     *
     * @var $certificate string
     */
    private $certificate;


    /**
     * passphrase for type of pem certificate
     *
     * @var $passphrase string
     */
    private $passphrase;

    /**
     * choosed
     *
     * @var string
     */
    private $url;


    /**
     * Instantiate socket object
     *
     * @var $socket object
     */
    protected $socket;


    public function __construct($environment, $certificate, $passphrase)
    {
        // check empty
        if (empty($certificate)) {
            throw new ApnException('certificate file is empty');
        }

        // file exist and readable trial
        if(!is_readable($certificate)) {
            throw new ApnException(
                "Unable to read certificate file {$certificate}"
            );
        }

        $this->certificate = $certificate;
        $this->passphrase  = $passphrase;
        $this->environment = $environment;
        $this->url = $this->serviceURLs[$this->environment];
    }

    /**
     * Connects to Apple Push Notification service server.
     *
     * @return bool True if successful connected.
     * @throws ApnException if is unable to connect.
     */
    public function connect()
    {
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->certificate);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->passphrase);

        $this->socket = @stream_socket_client($this->url, $errno, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        if (!$this->socket) {
            throw new ApnException("ERROR: '{$errno}' - {$errstr}");
        }

        stream_set_blocking($this->socket, 0);
        stream_set_write_buffer($this->socket, 0);
        // todo log
//        $this->log("Connected to APNS");

        return true;
    }


    /**
     *
     * @param $deviceToken
     * @param $message
     */
    public function send($deviceToken, $message)
    {
        $payload = json_encode($message);
        $msg = chr(0) .
            pack('n', 32) .
            pack('H*', $deviceToken) .
            pack('n', strlen($payload)) .
            $payload;

        $result = fwrite($this->socket, $msg, strlen($msg));
        if (!$result){
            // todo log
//            echo 'Message not Delivered' . PHP_EOL;
        } else {
//            echo "Message successfully delivered" . PHP_EOL;
        }
    }

    /**
     * Disconnects from Apple Push Notifications service server.
     *
     * @return bool
     */
    public function disconnect()
    {
        if(is_resource($this->socket)) {
            return fclose($this->socket);
        }

        return false;
    }

}