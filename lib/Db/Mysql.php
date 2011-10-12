<?php
class Mysql extends Db
{
  public function create()
  {
    return "mysql {$this->connect()} -e 'create database if not exists {$this->wordpress["db"]}'";
  }

  public function dump($file)
  {
    return "mysqldump {$this->connect()} {$this->wordpress["db"]} --lock-tables=FALSE --skip-add-drop-table | sed -e 's|INSERT INTO|REPLACE INTO|' -e 's|CREATE TABLE|CREATE TABLE IF NOT EXISTS|' > $file";  
  }

  public function backup($file)
  {
    return "mysqldump {$this->connect()} {$this->wordpress["db"]} --add-drop-table | bzip2 -c > $file";
  }

  public function merge($file)
  {
    return "mysql {$this->connect()} {$this->wordpress["db"]} --force < $file";
  }

  public function connect()
  {
    return "-u {$this->wordpress["db_user"]} -p --password={$this->wordpress["db_password"]} --host={$this->wordpress["db_host"]}"; 
  }
}
?>
