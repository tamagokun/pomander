<?php
namespace Pomander\Scm;

class Svn extends \Pomander\Scm
{
  public function create()
  {
    return "svn co {$this->repository} {$this->app->env->deploy_to} --quiet";
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
