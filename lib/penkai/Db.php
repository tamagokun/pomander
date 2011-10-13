<?php
abstract class Db
{
  public $wordpress;

  public function __construct($wordpress)
  {
    $this->wordpress = $wordpress;
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
