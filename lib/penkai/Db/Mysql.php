<?php
class Mysql extends Db
{
  public function create()
  {
    return "mysql {$this->connect()} -e 'create database if not exists {$this->config["db"]}'";
  }

  public function dump($file)
  {
    return "mysqldump {$this->connect()} {$this->config["db"]} --lock-tables=FALSE --skip-add-drop-table | sed -e 's|INSERT INTO|REPLACE INTO|' -e 's|CREATE TABLE|CREATE TABLE IF NOT EXISTS|' > $file";  
  }

  public function backup($file)
  {
    return "mysqldump {$this->connect()} {$this->config["db"]} --add-drop-table | bzip2 -c > $file";
  }

  public function merge($file)
  {
    return "mysql {$this->connect()} {$this->config["db"]} --force < $file";
  }

  public function connect()
  {
    return "-u {$this->config["db_user"]} -p --password={$this->config["db_password"]} --host={$this->config["db_host"]}"; 
  }
}
?>
