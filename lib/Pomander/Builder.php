<?php
namespace Pomander;

class Builder
{
	public $runfile;

	public function config()
	{
		foreach(glob("deploy/*{.php,.yml,.yaml}", GLOB_BRACE) as $config)
		{
			$name = basename($config, ".".pathinfo($config, PATHINFO_EXTENSION));
			$config = $this->is_yaml($config)? \Spyc::YAMLLoad($config) : $config;
			$this->environment_task(new Environment($name), $config);
		}
	}

	public function has_environments()
	{
		return count(glob("deploy/*{.php,.yml,.yaml}", GLOB_BRACE)) > 0;
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

		$tasks = glob(dirname(__DIR__).'/tasks/*.php');
		foreach($tasks as $task) require $task;
		if($first)
		{
			$this->runfile = $this->resolve_runfile(getcwd());
			$directory = $this->runfile ? dirname($this->runfile) : getcwd();
			if(!@chdir($directory))
					throw new \Exception("Couldn't change to directory '$directory'");
			else
				puts("(in $directory)");
			$this->inject_default($app);
		}
	}

/* protected */
	protected function environment_task($env, $config)
	{
		$builder = $this;
		task($env->name, function($app) use($env, $config, $builder) {
			info("environment",$env->name);
			\phake\Builder::$global->clear();
			$builder->run(false);
			$app->env = $env;
			if($builder->runfile) require $builder->runfile;
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
		$app->env = $env;
		if($this->runfile) require $this->runfile;
		$config = $this->is_yaml($file)? \Spyc::YAMLLoad($file) : $file;
		if(is_string($config)) require($config);
		else $env->set($config);
		$app->env = $env;
		$app->env->setup();
		$app->reset();
	}

	protected function resolve_runfile($directory)
	{
		$runfiles = array('Phakefile','Phakefile.php','Pomfile','Pomfile.php');
		do
		{
			foreach($runfiles as $r)
			{
				$candidate = $directory.'/'.$r;
				if(file_exists($candidate)) return $candidate;
			}
			if($directory == '/') return false;
			$directory = dirname($directory);
		} while (true);
	}

/* private */
	private function is_yaml($file)
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		return $ext == "yml" || $ext == "yaml";
	}
}
