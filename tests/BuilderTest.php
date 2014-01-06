<?php

use \Pomander\Builder;

class BuilderTest extends TestCase
{
  public function testDetectEnvironments()
  {
    $builder = $this->builder();
    $builder->config();

    $app = builder()->get_application();
    $task_names = array_keys($app->get_tasks());
    $this->assertSame(array('development', 'test', 'norelease'), $task_names);
  }

  public function testCanFirstRun()
  {
    $builder = $this->builder();
    $builder->run();

    $app = builder()->get_application();
    $app->invoke('environment');

    $this->expectOutputString("(in ".__DIR__.")\n");
  }

  public function testCanLoadEnvironments()
  {
    ob_start();
    $builder = $this->builder();
    $builder->run();
    ob_end_clean();

    $app = builder()->get_application();
    // default environment is development
    $app->invoke("testing");
    $this->expectOutputString("hello");

    // load test and check environment
    ob_start();
    $app->invoke("test");
    ob_end_clean();
    $this->assertSame("git@github.com:tamagokun/pomander.git", $app->env->repository);

    // re-load development for sanity
    ob_start();
    $app->invoke("development");
    ob_end_clean();
    $app->invoke("testing");
    $this->expectOutputString("hellohello");
  }

  public function testLoadsRunfile()
  {
    ob_start();
    $builder = $this->builder();
    $builder->run();
    ob_end_clean();

    $app = builder()->get_application();
    $app->invoke("from_pomfile");
    $this->expectOutputString("Pom!");

    ob_start();
    $app->invoke("test");
    ob_end_clean();
    $app->invoke("from_pomfile");
    $this->expectOutputString("Pom!Pom!");
  }

  protected function builder()
  {
    // clear tasks and change directory
    chdir(__DIR__);
    phake\Builder::$global = new phake\Builder;
    return new Builder();
  }

}
