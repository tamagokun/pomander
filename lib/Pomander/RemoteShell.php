<?php
namespace Pomander;

class RemoteShell
{
	protected $host;

	public function __construct($host)
	{
		$this->host = $host;
	}

	public function run($cmd)
	{
		$status = 0;
		$output = array();
		exec("ssh {$this->host} \"$cmd\"", $output, $status);
		return array($status, $output);	
	}
}
