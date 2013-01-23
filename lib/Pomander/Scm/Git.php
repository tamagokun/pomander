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
		$branch = isset($this->app["branch"])? $this->app["branch"] : $this->app->env->revision;
		return "git fetch origin && git reset --hard $branch";
	}

	public function revision()
	{
		return "echo -e `git log --pretty=format:'%H%d' -n 1`";
	}
}
?>
