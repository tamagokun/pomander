<?php
require_once(__DIR__."/helpers.php");
require_once(__DIR__."/Environment.php");
require_once(__DIR__."/../spyc.php");

builder()->get_application()->default_env = "development";

function has_environments()
{
  $environments = glob("deploy/*.yml");
  return (count($environments) > 0);
}

function config()
{
  $environments = glob("deploy/*.yml");
  foreach($environments as $config)
  {
    $configs[basename($config,".yml")] = Spyc::YAMLLoad($config);
  }
  load_environments($configs);
}

function load_environments($config)
{
  foreach($config as $env_name=>$environment)
  {
    $env = new Environment($env_name,$environment);
    task($env_name, function($app) use($env) {
      info("environment",$env->name);
      $app->env = $env;
      $app->reset();
    });
  }
}

//core tasks
task("environment",function($app) {
  if(!has_environments())
    warn("config","unable to locate any environments. try running 'config'");
  if(!isset($app->env))
    $app->invoke($app->default_env);
});

task('app','environment',function($app) {
  $app->env->multi_role_support("app",$app);
});

task('db','environment',function($app) {
  $app->env->multi_role_support("db",$app);
});

//utils

function run()
{
  $cmd = implode(" && ",flatten(func_get_args()));
  echo builder()->get_application()->env->exec($cmd);
}

function put($what,$where)
{
  builder()->get_application()->env->put($what,$where);
}

function get($what,$where)
{
  builder()->get_application()->env->get($what,$where);
}

require_once_dir("tasks/*.php");
?>
