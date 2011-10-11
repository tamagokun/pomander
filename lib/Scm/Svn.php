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

  public function revision()
  {
    return "svn info | grep 'Revision:' | cut -f2 -d\\";
  }

}
?>
