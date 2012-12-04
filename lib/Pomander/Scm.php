<?php
namespace Pomander;

abstract class Scm
{
  public $repository,$app;

  public function __construct($repository)
  {
    $this->repository = $repository;
    $this->app = builder()->get_application();
  }

  public function create($location)
  {
    return "";
  }

  public function update()
  {
    return "";
  }

  public function revision()
  {
    return "";
  }
}
?>
