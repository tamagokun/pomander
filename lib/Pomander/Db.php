<?php
namespace Pomander;

abstract class Db
{
  public $config;

  public function __construct($config)
  {
    $this->config = $config;
  }

  public function create()
  {
    return "";
  }

  public function dump($file, $args="")
  {
    return "";
  }

  public function backup($file, $args="")
  {
    return "";
  }

  public function merge($file, $args="")
  {
    return "";
  }
}
?>
