<?php

use \Pomander\Environment;

class EnvironmentTest extends TestCase
{

  public function testConstructor()
  {
    $env = new Environment("test");
    $this->assertSame($env->name, "test");
    $this->assertTrue($env->remote_cache);
  }

  public function testSetSettingsMagic()
  {
    $env = new Environment("test");
    // basic set
    $env->backup = false;
    $this->assertFalse($env->backup);

    // array merging
    $env->wordpress = array(
      "version" => "latest",
      "cache"   => true
    );
    $env->wordpress = array(
      "version" => "1.0"
    );
    $this->assertTrue($env->wordpress["cache"]);
    $this->assertSame($env->wordpress["version"], "1.0");
  }

  public function testSetSettings()
  {
    $env = new Environment("test");
    // basic set
    $env->set(array("backup" => false));
    $this->assertFalse($env->backup);

    // array merging
    $env->set(array("wordpress" => array(
      "version" => "latest",
      "cache"   => true
    )));
    $env->set(array("wordpress" => array(
      "version" => "1.0"
    )));
    $this->assertTrue($env->wordpress["cache"]);
    $this->assertSame($env->wordpress["version"], "1.0");
  }

  public function testGetSettings()
  {
    $env = new Environment("test");
    $env->backup = false;
    $backup = $env->backup;
    $this->assertFalse($backup);
  }

  public function testSetupDevelopment()
  {
    $env = new Environment("development");
    $env->releases = true;
    $env->setup();

    $dir = getcwd();
    $this->assertSame($env->current_dir, $dir);
    $this->assertSame($env->releases_dir, $dir);
    $this->assertSame($env->release_dir, $dir);
    $this->assertSame($env->shared_dir, $dir);
    $this->assertSame($env->cache_dir, $dir);
  }

  public function testSetup()
  {
    $env = new Environment("test");
    $env->deploy_to = "/var/www/html";
    $env->releases = true;
    $env->setup();

    $this->assertSame($env->current_dir, $env->deploy_to.'/current');
    $this->assertSame($env->releases_dir, $env->deploy_to.'/releases');
    $this->assertSame($env->release_dir, $env->current_dir);
    $this->assertSame($env->shared_dir, $env->deploy_to.'/shared');
    $this->assertSame($env->cache_dir, $env->shared_dir.'/cached_copy');

    $env->releases = false;
    $env->setup();

    $this->assertSame($env->current_dir, $env->deploy_to);
    $this->assertSame($env->releases_dir, $env->deploy_to);
    $this->assertSame($env->release_dir, $env->deploy_to);
    $this->assertSame($env->shared_dir, $env->deploy_to);
    $this->assertSame($env->cache_dir, $env->deploy_to);
  }

  public function testRoles()
  {
    ob_start();
    $env = new Environment("test");

    $env->role("app");
    $this->assertSame($env->target, null);
    $this->assertFalse($env->next_role("app"));

    $env->role("db");
    $this->assertSame($env->target, null);
    $this->assertFalse($env->next_role("db"));

    $env = new Environment("test");
    $env->app = "test.hostname";
    $env->role("app");
    $this->assertSame($env->target, "test.hostname");
    $this->assertFalse($env->next_role("app"));

    $env = new Environment("test");
    $env->app = array(
      "test.hostname",
      "test2.hostname"
    );
    $env->role("app");
    $this->assertSame($env->target, "test.hostname");
    $this->assertTrue($env->next_role("app"));
    $env->role("app");
    $this->assertSame($env->target, "test2.hostname");
    $this->assertFalse($env->next_role("app"));
    ob_end_clean();
  }

}

