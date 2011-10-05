<?php
class Environment
{
  public $name;
  private $config;

  public function __construct($env_name,$args=null)
  {
    $this->name = $env_name;
    $this->config = (array) $args;
  }

  public function __get($prop)
  {
    if( array_key_exists($prop, $this->config))
      return $this->config[$prop];
    return null;
  }

  public function __isset($prop)
  {
    return isset($this->config[$prop]);
  }

  public function __set($prop,$value)
  {
    $this->config[$prop] = $value;
  }

}
?>
