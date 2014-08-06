<?php
namespace Pomander;

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

    ob_end_clean();
    $this->assertFileExists($app->env->deploy_to."/.git");
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

    ob_end_clean();
    $this->assertFileExists($app->env->release_dir);
    $this->assertTrue($sha == $new_sha);
  }

  public function testDeployBranch()
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

    $app->env->branch = "gh-pages";
    $app->invoke("deploy:update");
    sleep(1);
    $app->reset();
    $expected_sha = $app->env->scm->get_commit_sha($app->env->branch);
    $current_sha = shell_exec("cd {$app->env->release_dir} && {$app->env->scm->revision()}");

    ob_end_clean();
    $this->assertFileExists($app->env->release_dir);
    $this->assertSame($expected_sha, trim($current_sha));

    ob_start();
    $app->env->branch = "";
    $app->env->revision = "0.3.5";
    $app->invoke("deploy:update");

    $sha = shell_exec("cd {$app->env->release_dir} && {$app->env->scm->revision()}");

    ob_end_clean();
    $this->assertSame(trim($sha), "46bbf9cfe5cfa3656f1246870ff98656e27761e7");
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

    ob_end_clean();
    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertTrue(readlink($app->env->current_dir) == $app->env->release_dir);
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
    sleep(1);
    $app->invoke("deploy:update");
    $app->reset();
    sleep(1);
    $app->invoke("deploy:update");
    $app->invoke("deploy:cleanup");

    ob_end_clean();
    $this->assertFileExists($app->env->release_dir);
    $this->assertCount(2, glob($app->env->releases_dir."/*"));
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

    ob_end_clean();
    $this->assertFileExists($app->env->release_dir);
    $this->assertFileExists($app->env->releases_dir);
    $this->assertFileExists($app->env->shared_dir);
    $this->assertFileExists($app->env->cache_dir);
    $this->assertFileExists($app->env->cache_dir."/.git");

    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertTrue(readlink($app->env->current_dir) == $app->env->release_dir);
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

    ob_end_clean();
    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertTrue(readlink($app->env->current_dir) == $app->env->release_dir);
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
    sleep(1);
    $app->invoke("deploy:update");

    $app->env->finalized = true;

    $app->invoke("rollback");

    $release = readlink($app->env->current_dir);

    ob_end_clean();
    $this->assertFileExists($app->env->current_dir);
    $this->assertTrue(is_link($app->env->current_dir));
    $this->assertFileExists($release);
    $this->assertFalse(readlink($app->env->current_dir) == $app->env->release_dir);
  }

  public function testMultiRole()
  {
    ob_start();
    $this->clean();

    $app = $this->app(array("multirole", "check"));

    $app->invoke("multirole");
    $app->invoke("check");

    ob_end_clean();
    $this->assertTrue(3 === $app->env->role_count);
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
