<?php
namespace Pomander;

class Environment
{
	public $name,$target,$scm,$adapter;
	private $config,$shell,$mysql;
	private $roles;

	public function __construct($env_name)
	{
		$this->name = $env_name;
		$this->config = $this->defaults();
		$this->roles = array("app"=>null,"db"=>null);
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

	public function set($options)
	{
		foreach((array) $options as $key=>$option)
			if($option || $option === false) $this->config[$key] = $option;
	}

	public function setup()
	{
		if($this->name == "development") $this->releases = false;
		if($this->releases === false)
		{
			$this->current_dir = $this->deploy_to;
			$this->releases_dir = $this->deploy_to;
			$this->release_dir = $this->deploy_to;
			$this->shared_dir = $this->deploy_to;
			$this->cache_dir = $this->deploy_to;
		}else
		{
			$this->current_dir = $this->deploy_to.'/current';
			$this->releases_dir = $this->deploy_to.'/releases';
			$this->release_dir = $this->current_dir;
			$this->shared_dir = $this->deploy_to.'/shared';
			$this->cache_dir = $this->shared_dir.'/cached_copy';
		}
		$this->init_scm_adapter();
	}

	public function __call($name, $arguments)
	{
		$this->config[$name] = array_shift($arguments);
		return $this;
	}

	public function __get($prop)
	{
		if(array_key_exists($prop, $this->config)) return $this->config[$prop];
		return null;
	}

	public function __isset($prop) { return isset($this->config[$prop]); }
	public function __set($prop,$value) { $this->config[$prop] = $value; }

	public function new_release()
	{
		return date('Ymdhis');
	}

	public function role($key)
	{
		if(!$this->$key) return false;
		if( !$this->roles[$key] )
		{
			$targets = is_array($this->$key)? $this->$key : array($this->$key);
			$this->roles[$key] = new Role($targets);
			return $this->update_target($this->roles[$key]->target());
		}else
		{
			return $this->update_target($this->roles[$key]->next());
		}
	}

	public function next_role($key)
	{
		if( !$this->roles[$key]) return false;
		return $this->roles[$key]->has_target($this->roles[$key]->current+1);
	}

	public function multi_role_support($role,$app)
	{
		$this->role($role);
		foreach($app->top_level_tasks as $task_name)
		{
			if( in_array($role,$app->resolve($task_name)->dependencies()) )
				return $this->inject_multi_role_after($role,$task_name);
			else
			{
				foreach($app->resolve($task_name)->dependencies() as $dependency)
				{
					if( in_array($role,$app->resolve($dependency)->dependencies()) )
						return $this->inject_multi_role_after($role,$dependency);
				}
			}
		}
	}

	public function exec($cmd)
	{
		if(!$this->target) return exec_cmd($cmd);
		if(!$this->shell)
		{
			$host = isset($this->user)? "{$this->user}@{$this->target}" : $this->target;
			$this->shell = new RemoteShell($host);
		}

		return $this->shell->run($cmd);
	}

	public function put($what,$where)
	{
		if($this->target)
			$cmd = "{$this->rsync_cmd} {$this->rsync_flags} $what {$this->user}@{$this->target}:$where";
		else
			$cmd = "cp -r $what $where";
		return exec_cmd($cmd);
	}

	public function get($what,$where)
	{
		if($this->target)
			$cmd = "{$this->rsync_cmd} {$this->rsync_flags} {$this->user}@{$this->target}:$what $where";
		else
			$cmd = "cp -r $what $where";
		return exec_cmd($cmd);
	}

	private function defaults()
	{
		$defaults = array(
			"url"=>"",
			"user"=>"",
			"repository"=>"",
			"revision"=>"origin/master",
			"remote_cache"=>true,
			"releases"=>false,
			"deploy_to"=>getcwd(),
			"backup"=>false,
			"app"=>"",
			"db"=>"",
			"scm"=>"git",
			"adapter"=>"mysql",
			"rsync_cmd"=>"rsync",
			"umask"=>"002",
			"rsync_flags"=>"-avuzPO --quiet",
			"db_backup_flags"=>"--lock-tables=FALSE --skip-add-drop-table | sed -e 's|INSERT INTO|REPLACE INTO|' -e 's|CREATE TABLE|CREATE TABLE IF NOT EXISTS|'",
			"db_swap_url"=>true
		);
		return $defaults;
	}

	private function update_target($target)
	{
		if( !$target ) return false;
		if( $this->target == $target ) return true;
		if( $this->shell ) $this->shell = null;
		$this->target = $target;
		info("target",$this->target);
		return true;
	}

	private function init_scm_adapter()
	{
		$scm = "\\Pomander\\Scm\\".ucwords(strtolower($this->config["scm"]));
		if( !$this->scm = new $scm($this->repository) )
			abort("scm","There is no recipe for {$this->config["scm"]}, perhaps create your own?");
		$adapter = "\\Pomander\\Db\\".ucwords(strtolower($this->config["adapter"]));
		if( !$this->adapter = new $adapter($this->database) )
			abort("db","There is no recipe for {$this->config["adapter"]}, perhaps create your own?");
	}

	private function inject_multi_role_after($role,$task_name)
	{
		after($task_name,function($app) use($task_name,$role) {
			if( $app->env->next_role($role) )
			{
				$app->reset();
				$app->invoke($task_name);
			}
		});
	}
}
?>
