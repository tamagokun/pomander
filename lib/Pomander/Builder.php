<?php
namespace Pomander;

class Builder
{
	public function config()
	{
		foreach(glob("deploy/*.yml") as $config)
			$configs[basename($config,".yml")] = \Spyc::YAMLLoad($config);
		$this->load_environments($configs);
	}

	public function has_environments()
	{
		return count(glob("deploy/*.yml")) > 0;
	}

	public function load_environments($config)
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

	public function load($plugin)
	{
		if(!class_exists($plugin))
			$plugin = "\\Pomander\\$plugin";
		$plugin::load();
	}

	public function run()
	{
		$builder = $this;
		if($this->has_environments()) $this->config();
		builder()->get_application()->default_env = "development";

		task("environment",function($app) use($builder) {
			if(!$builder->has_environments())
				warn("config","unable to locate any environments. try running 'config'");
			if(!isset($app->env)) $app->invoke($app->default_env);
		});

		task('app','environment',function($app) {
			$app->env->multi_role_support("app",$app);
		});

		task('db','environment',function($app) {
			$app->env->multi_role_support("db",$app);
		});

		require dirname(__DIR__).'/tasks/default.php';
	}
}
