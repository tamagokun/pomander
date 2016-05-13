<?php
namespace Pomander;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

class RemoteShell
{
    /**
     * SSH
     *
     * @var SSH2
     */
    protected $shell;

    protected $host, $port, $user, $auth, $key_pass;

    public function __construct($host, $port, $user, $auth, $key_pass)
    {
        $this->host = $host;
        $this->port = $port? $port : 22;
        $this->user = $user;
        $this->auth = $auth;
        $this->key_pass = $key_pass;
        $this->connect();
    }

    public function run($cmd)
    {
        $this->shell->enablePTY();
        $this->shell->setTimeout(0);
        $this->shell->exec($cmd);

        $output = $this->process();
        $status = $this->shell->getExitStatus();
        if($status === false) $status = -1;

        return array($status, array_filter(explode("\r\n",$output), 'trim'));
    }

    public function write($cmd)
    {
        $this->shell->write("$cmd\n");
    }

/* protected */
    protected function connect()
    {
        $this->shell = new SSH2($this->host, $this->port);
        if (file_exists($this->auth)) {
            $key = new RSA();
            if ($this->key_pass) {
                $key->setPassword($this->key_pass);
            }
            $key_status = $key->loadKey(file_get_contents($this->auth));
            if(!$key_status) abort("ssh", "Unable to load RSA key.");
        } else {
            $key = $this->auth;
        }

        if(!$this->shell->login($this->user, $key))
            abort("ssh", "Login failed.");
    }

    /**
     * Process remote command execution
     *
     * @return string
     */
    protected function process()
    {
        $output = "";
        $offset = 0;
        $this->shell->_initShell();
        while( true ) {
            $temp = $this->shell->_get_channel_packet(SSH2::CHANNEL_EXEC);
            switch( true ) {
                case $temp === true:
                case $temp === false:
                    return $output;
                default:
                    $output .= $temp;
                    if( $this->handle_data(substr($output, $offset)) ) {
                        $offset = strlen($output);
                    }
            }
        }
    }

    protected function handle_data($output)
    {
        // hosts authenticity
        $regex = '/(The authenticity of host .* \(yes\/no\))/s';

        if (preg_match($regex, $output) > 0) {
            $this->write("yes");
            return true;
        }

        // key password
        $regex = '/Enter passphrase for key .*/';

        if( preg_match($regex, $output) > 0 ) {
            $this->write($this->key_pass);
            return true;
        }

        return false;
    }
}
