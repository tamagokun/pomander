<?php
namespace Pomander;

class RemoteShell
{
	protected $host, $user, $auth, $shell;

	public function __construct($host, $user, $auth)
	{
		$this->host = $host;
		$this->user = $user;
		$this->auth = $auth;
		$this->connect();
	}

	public function run($cmd)
	{
		$this->shell->exec($cmd);

		$status = -1;
		$output = '';
		while ($status < 0) {
			$temp = $this->shell->_get_channel_packet(NET_SSH2_CHANNEL_EXEC);
			if($temp === true) $status = 0;
			elseif($temp === false) $status = 1;
			else
			{
				$output .= $temp;
				$this->handle_data($temp);
			}
		}
		$status = isset($this->shell->exit_status)? $this->shell->exit_status : $status;
		return array($status, array_filter(explode("\r\n",$output), 'trim'));
		return array($status, $output);
	}

	public function write($cmd)
	{
		$this->shell->_send_channel_packet(NET_SSH2_CHANNEL_EXEC, "$cmd\n");
	}

/* protected */
	protected function connect()
	{
		//TODO: Support other ports
		$this->shell = new SSH2($this->host);
		if(file_exists($this->auth))
		{
			$key = new \Crypt_RSA();
			$key_status = $key->loadKey(file_get_contents($this->auth));
			if(!$key_status) abort("ssh", "Unable to load RSA key.");
		}else
		{
			$key = $this->auth;
		}

		if(!$this->shell->login($this->user, $key))
			abort("ssh", "Login failed.");
	}

	protected function handle_data($output)
	{
		if(preg_match('/(The authenticity of host .* \(yes\/no\))/s', $output))
		{
			$this->write("yes");
		}
	}
}
