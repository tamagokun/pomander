<?php
namespace Pomander\Db;

class Mysql extends \Pomander\Db
{
  public function create()
  {
    return "mysql {$this->connect()} -e 'create database if not exists {$this->config["name"]}'";
  }

	public function destroy()
	{
		return "mysql {$this->connect()} -e 'drop database {$this->config["name"]}; create database {$this->config["name"]};'";
	}

  public function dump($file, $args = "")
  {
    return "mysqldump {$this->connect()} {$this->config["name"]} $args > $file";
  }

  public function backup($file, $args = "")
  {
    return "mysqldump {$this->connect()} {$this->config["name"]} $args | bzip2 -c > $file";
  }

  public function merge($file, $args = "")
  {
    $args.= " --force";
    return "mysql {$this->connect()} {$this->config["name"]} $args < $file";
  }

  public function connect()
  {
    return "-u {$this->config["user"]} -p --password='{$this->config["password"]}' --host={$this->config["host"]}";
  }

	public function restore($backup)
	{
		return "mysql {$this->connect()} {$this->config["name"]} < bzip2 -dc $backup";
	}
}
?>
