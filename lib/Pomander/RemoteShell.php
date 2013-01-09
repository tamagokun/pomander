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
		passthru("ssh {$this->host} \"$cmd\"", $status);
		return $status;	
	}
}
