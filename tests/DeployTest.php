<?php

use \Pomander\Builder;

class DeployTest extends TestCase
{

  public function testSetup()
  {
    ob_start();
    // releases
    $this->clean();
    $app = $this->app(array("test", "deploy:setup"));

    $app->invoke("test");
    $app->invoke("deploy:setup");

    $this->assertFileExists($app->env->releases_dir);
    $this->assertFileExists($app->env->shared_dir);
    $this->assertFileExists($app->env->cache_dir);
    $this->assertFileExists($app->env->cache_dir."/.git");

    // (no releases)
    $this->clean();
    $app = $this->app(array("norelease", "deploy:setup"));

    $app->invoke("norelease");
    $app->invoke("deploy:setup");

    $this->assertFileExists($app->env->deploy_to."/.git");
    ob_end_clean();
  }

  public function testUpdateCode()
  {
    ob_start();
    // releases
    $this->clean();
    $app = $this->app(array("test", "deploy:setup", "deploy:update"));

    $app->invoke("test");
    $app->invoke("deploy:setup");

    // set absolute paths
    $app->env->deploy_to = __DIR__."/".$app->env->deploy_to;
    $app->env->setup();

    // copy current sha and go back a bit.
    $sha = shell_exec("cd {$app->env->cache_dir} && {$app->env->scm->revision()}");
    shell_exec("cd {$app->env->cache_dir} && git reset --hard HEAD~3");

    $app->invoke("deploy:update");
    $new_sha = shell_exec("cd {$app->env->release_dir} && {$app->env->scm->revision()}");

    $this->assertFileExists($app->env->release_dir);
    $this->assertTrue($sha == $new_sha);
    ob_end_clean();
  }

  public function testFinalize()
  {
    ob_start();
    // releases
    $this->clean();
    $app = $this->app(array("test", "deploy:setup", "deploy:update", "deploy:finalize"));

    $app->invoke("test");
    $app->invoke("deploy:setup");

    // set absolute paths
    $app->env->deploy_to = __DIR__."/".$app->env->deploy_to;
    $app->env->setup();

    $app->invoke("deploy:update");
    $app->invoke("deploy:finalize");

    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertTrue(readlink($app->env->current_dir) == $app->env->release_dir);
    ob_end_clean();
  }

  public function testCleanUp()
  {
    ob_start();
    // releases
    $this->clean();
    $app = $this->app(array("test", "deploy:setup", "deploy:update", "deploy:cleanup"));

    $app->invoke("test");
    $app->invoke("deploy:setup");

    // set absolute paths
    $app->env->deploy_to = __DIR__."/".$app->env->deploy_to;
    $app->env->setup();

    $app->invoke("deploy:update");
    $app->reset();
    $app->invoke("deploy:update");
    $app->reset();
    $app->invoke("deploy:update");
    $app->invoke("deploy:cleanup");

    $this->assertFileExists($app->env->release_dir);
    $this->assertCount(2, glob($app->env->releases_dir."/*"));
    ob_end_clean();
  }

  public function testCold()
  {
    ob_start();
    // releases
    $this->clean();
    $app = $this->app(array("test", "deploy:cold"));

    $app->invoke("test");

    // set absolute paths
    $app->env->deploy_to = __DIR__."/".$app->env->deploy_to;
    $app->env->setup();

    $app->invoke("deploy:cold");

    $this->assertFileExists($app->env->release_dir);
    $this->assertFileExists($app->env->releases_dir);
    $this->assertFileExists($app->env->shared_dir);
    $this->assertFileExists($app->env->cache_dir);
    $this->assertFileExists($app->env->cache_dir."/.git");

    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertTrue(readlink($app->env->current_dir) == $app->env->release_dir);

    ob_end_clean();
  }

  public function testDefault()
  {
    ob_start();
    // releases
    $this->clean();
    $app = $this->app(array("test", "deploy:setup", "deploy"));

    $app->invoke("test");
    $app->invoke("deploy:setup");

    // set absolute paths
    $app->env->deploy_to = __DIR__."/".$app->env->deploy_to;
    $app->env->setup();

    $app->invoke("deploy");

    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertTrue(readlink($app->env->current_dir) == $app->env->release_dir);

    ob_end_clean();
  }

  public function testRollback()
  {
    ob_start();
    // releases
    $this->clean();

    $app = $this->app(array("test", "deploy:setup", "deploy"));

    $app->invoke("test");
    $app->invoke("deploy:setup");

    // set absolute paths
    $app->env->deploy_to = __DIR__."/".$app->env->deploy_to;
    $app->env->setup();

    $app->invoke("deploy:update");
    $app->reset();
    $app->invoke("deploy:update");

    $app->env->finalized = true;

    $app->invoke("rollback");

    $release = readlink($app->env->current_dir);

    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertFileExists($release);
    $this->assertFalse(readlink($app->env->current_dir) == $app->env->release_dir);

    ob_end_clean();
  }

// private
  protected function app($tasks = array(), $args = array())
  {
    // clear tasks and change directory
    chdir(__DIR__);
    $app = new \phake\Application();
    $app->set_args($args);
    $app->top_level_tasks = $tasks;
    $app->dir = __DIR__;

    \phake\Builder::$global = new \phake\Builder($app);

    $builder = new Builder();
    $builder->run();

    return builder()->get_application();
  }

  protected function clean()
  {
    shell_exec("rm -rf ".__DIR__."/test_release && mkdir -p ".__DIR__."/test_release");
  }
}
