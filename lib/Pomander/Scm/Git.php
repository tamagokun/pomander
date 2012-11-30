<?php
namespace Pomander\Scm;

class Git extends \Pomander\Scm
{
  public function create()
  {
    return "git clone {$this->repository} {$this->app->env->deploy_to}";
  }

  public function update()
  {
    $revision = ( isset($this->app->env->revision))? $this->app->env->revision : "origin/master";
    return "git fetch origin && git reset --hard $revision";
  }

	public function last_revision()
	{
		return "echo `git rev-parse HEAD` > LAST_REVISION";
	}

  public function revision()
  {
    return "echo -e `git log --pretty=format:'%H%d' -n 1`";
  }

	public function rollback()
	{
		return "git reset --hard `cat LAST_REVISION`";
	}

}
?>
