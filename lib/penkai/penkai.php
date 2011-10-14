<?php
set_include_path(dirname(__FILE__)."/../");
require_once("penkai/helpers.php");
require_once("penkai/Environment.php");
require_once("spyc.php");

builder()->get_application()->config_path = getcwd()."/config.yml";
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
  multi_role_support("app",$app);
});

task('db','environment',function($app) {
  multi_role_support("db",$app);
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

function multi_role_support($role,$app)
{
  $app->env->role($role);
  foreach($app->top_level_tasks as $task_name)
  {
    if( in_array($role,$app->resolve($task_name)->dependencies()) )
      return inject_multi_role_after($role,$task_name);
    else
    {
      foreach($app->resolve($task_name)->dependencies() as $dependency)
      {
        if( in_array($role,$app->resolve($dependency)->dependencies()) )
          return inject_multi_role_after($role,$dependency);
      }
    }
  }
}

function inject_multi_role_after($role,$task_name)
{
  after($task_name,function($app) use($task_name,$role) {
    if( $app->env->next_role($role) )
    {
      $app->reset();
      $app->invoke($task_name);
    }
  });
}

require_once_dir("tasks/*.php");
?>
