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
    $revision = ( isset($this->app->env->revision))? $this->app->env->revision : "origin/master";
    return "git fetch origin && git reset --hard $revision";
  }

  public function revision()
  {
    return "echo -e `git log --pretty=format:'%H%d' -n 1`";
  }
}
?>
