<?php
namespace Pomander;

class Builder
{
	public function config()
	{
		foreach(glob("deploy/*{.php,.yml}", GLOB_BRACE) as $config)
		{
			$name = basename($config, ".".pathinfo($config, PATHINFO_EXTENSION));
			$config = $this->is_yaml($config)? \Spyc::YAMLLoad($config) : $config;
			$this->environment_task(new Environment($name), $config);
		}
	}

	public function has_environments()
	{
		return count(glob("deploy/*{.php,.yml}", GLOB_BRACE)) > 0;
	}

	public function load($plugin)
	{
		if(!class_exists($plugin))
		{
			$plugin = "\\Pomander\\$plugin";
			if(!class_exists($plugin))
				return abort("load","Could not load plugin {$plugin}");
		}
		$plugin::load();
	}

	public function run($first = true)
	{
		$builder = $this;
		if($this->has_environments()) $this->config();
		$app = builder()->get_application();
		$app->default_env = "development";
		$app->can_rollback = false;

		task("environment",function($app) use($builder) {
			if(!$builder->has_environments())
				abort("config","unable to locate any environments. try running 'pom config'");
			if(!isset($app->env)) $app->invoke($app->default_env);
		});

		task('app','environment',function($app) {
			$app->env->multi_role_support("app",$app);
		});

		task('db','environment',function($app) {
			$app->env->multi_role_support("db",$app);
		});

		require dirname(__DIR__).'/tasks/default.php';
		if($first) $this->inject_default($app);
	}

/* protected */
	protected function environment_task($env, $config)
	{
		$builder = $this;
		task($env->name, function($app) use($env, $config, $builder) {
			info("environment",$env->name);
			\phake\Builder::$global->clear();
			$builder->run(false);
			if(is_string($config)) require($config);
			else $env->set($config);
			$app->env = $env;
			$app->env->setup();
			$app->reset();
		});
	}

	protected function inject_default($app)
	{
		$env = new Environment($app->default_env);
		$file = glob("deploy/{$env->name}.*");
		if(count($file) < 1) return;
		$file = array_shift($file);
		$config = $this->is_yaml($file)? \Spyc::YAMLLoad($file) : $file;
		if(is_string($config)) require($config);
		else $env->set($config);
		$app->env = $env;
		$app->env->setup();
		$app->reset();
	}

/* private */
	private function is_yaml($file)
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		return $ext == "yml" || $ext == "yaml";
	}
}
