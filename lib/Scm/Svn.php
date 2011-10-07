<?php
class Svn extends Scm
{
  public function create()
  {
    global $deploy;
    return "svn co {$this->repository} {$deploy->env->deploy_to} --quiet";
  }

  public function update()
  {
    return "svn update";
  }

}
?>
