<?php
namespace Pomander;

abstract class Method
{
    public $repository, $app;

    public function __construct($repository)
    {
        $this->repository = $repository;
        $this->app = builder()->get_application();
    }

    public function setup()
    {

    }

    public function update()
    {

    }

    public function finalize()
    {

    }
}
