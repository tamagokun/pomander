<?php
class Git extends Scm
{
  public function create()
  {
    return "git clone {$this->repository} {$this->app->env->deploy_to}";
  }

  public function update()
  {
    global $deploy;
    $revision = ( isset($this->app->env->revision))? $this->app->env->revision : "origin/master";
    return "git fetch origin && git reset --hard $revision";
  }

  public function revision()
  {
    return "echo -e `git log --pretty=format:'%H%d' -n 1`";
  }

}
?>
