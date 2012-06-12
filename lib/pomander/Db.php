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

  public function dump()
  {
    return "";
  }

  public function backup()
  {
    return "";
  }

  public function merge()
  {
    return "";
  }
}
?>
