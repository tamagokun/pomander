<?php
namespace Pomander\Scm;

class Git extends \Pomander\Scm
{
	public function create($location)
	{
		return "git clone {$this->repository} {$location}";
	}
	
	public function update()
	{
		if (!empty($this->app->env->branch)) {
			$cmd = "git reset --hard --quiet && git checkout {$this->app->env->branch} --quiet && git pull --quiet";
		} elseif (!empty($this->app->env->revision)) {
			$cmd = "git reset --hard {$this->app->env->revision} --quiet";
		} else {
			$cmd = 'git reset --hard HEAD --quiet && git pull --quiet';
		}
		
		if ($this->app->env->submodule !== false) {
			$cmd .= ' && git submodule update --init --recursive --quiet';
		}
		
		$cmd .= ' && git log --date=relative --format=format:"%C(bold blue)(%ar)%C(reset) // %an \'%s\' %C(bold green)(%h)%C(reset)" | head -1';
		
		return $cmd;
	}
	
	public function revision()
	{
		return "echo -e `git log --pretty=format:'%H' -n 1`";
	}
}
