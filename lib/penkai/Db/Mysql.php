<?php
class Mysql extends Db
{
  public function create()
  {
    return "mysql {$this->connect()} -e 'create database if not exists {$this->config["db"]}'";
  }

  public function dump($file, $args = "")
  {
    return "mysqldump {$this->connect()} {$this->config["db"]} $args > $file";  
  }

  public function backup($file, $args = "")
  {
    return "mysqldump {$this->connect()} {$this->config["db"]} $args | bzip2 -c > $file";
  }

  public function merge($file, $args = "")
  {
    $args.= " --force";
    return "mysql {$this->connect()} {$this->config["db"]} $args < $file";
  }

  public function connect()
  {
    return "-u {$this->config["db_user"]} -p --password={$this->config["db_password"]} --host={$this->config["db_host"]}"; 
  }
}
?>
