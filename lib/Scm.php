<?php
abstract class Scm
{
  public $repository;

  public function __construct($repository)
  {
    $this->repository = $repository;  
  }

  public function create()
  {
    return "";
  }

  public function update()
  {
    return "";  
  }
}
?>
