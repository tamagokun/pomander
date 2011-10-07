<?php
class Git extends Scm
{
  public function create()
  {
    global $deploy;
    return "git clone {$this->repository} {$deploy->env->deploy_to}";
  }

  public function update()
  {
    global $deploy;
    $revision = ( isset($deploy->env->revision))? $deploy->env->revision : "origin/master";
    return "git fetch origin && git reset --hard $revision";
  }

}
?>
