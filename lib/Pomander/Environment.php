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
		$this->init_scm_adapter();
	}

	public function set($options)
	{
		foreach((array) $options as $key=>$option)
			if($option && !empty($option)) $this->config[$key] = $option;
	}

	public function __get($prop)
	{
		if($prop == 'current_dir')  return $this->deploy_to.'/current/';
		if($prop == 'releases_dir') return $this->deploy_to.'/releases/';
		if($prop == 'shared_dir')   return $this->deploy_to.'/shared/';
		if($prop == 'cache_dir')    return $this->deploy_to.'/shared/cached_copy/';
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
			$this->roles[$key] = new Role($this->$key);
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

	public function connect()
	{
		if( !isset($this->target) ) return false;
		$this->shell = new \Net_SSH2($this->target);
		$key_path = home()."/.ssh/id_rsa";
		if( file_exists($key_path) )
		{
			$key = new \Crypt_RSA();
			$key_status = $key->loadKey(file_get_contents($key_path));
			if(!$key_status) abort("ssh","Unable to load RSA key");
		}else
		{
			if( isset($this->password) )
			$key = $this->password;
		}
		if(!$this->shell->login($this->user,$key))
			abort("ssh","Login failed");
	}

	public function exec($cmd)
	{
		if($this->target && !$this->shell) $this->connect();
		if($this->shell)
			return $this->shell->exec($cmd);
		else
		{
			return exec_cmd($cmd);
		}
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
			"rsync_flags"=>"-avuzPO --quiet"
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
		$this->config["scm"] = "\\Pomander\\Scm\\".ucwords(strtolower($this->config["scm"]));
		if( !$this->scm = new $this->config["scm"]($this->repository) )
			abort("scm","There is no recipe for {$this->config["scm"]}, perhaps create your own?");
		$this->config["adapter"] = "\\Pomander\\Db\\".ucwords(strtolower($this->config["adapter"]));
		if( !$this->adapter = new $this->config["adapter"]($this->wordpress) )
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
