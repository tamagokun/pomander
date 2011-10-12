<?php
class Mysql extends Db
{
  public $connection = "-u {$this->wordpress["db_user"]} -p --password={$this->wordpress["db_password"]} --host={$this->wordpress["db_host"]}";

  public function create()
  {
    return "mysql {$this->connection} -e 'create database if not exists {$this->wordpress["db"]}'";
  }

  public function dump($file)
  {
    return "mysqldump {$this->connection} {$this->wordpress["db"]} --lock-tables=FALSE --replace > $file";  
  }

  public function backup($file)
  {
    return "mysqldump {$this->connection} {$this->wordpress["db"]} --add-drop-table | bzip2 -c > $file";
  }

  public function merge($file)
  {
    return "mysql {$this->connection} {$this->wordpress["db"]} --force < $file";
  }
}
?>
