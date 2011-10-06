<?php
namespace deploy;

class Node extends phake\Node
{
  public function invoke($application) {
    global $deploy;
    
    foreach( $deploy->env->app as $target )
    {
      $deploy->env->connect($target);
      foreach ($this->dependencies() as $d) $application->invoke($d, $this->get_parent());
      foreach ($this->before as $t) $t->invoke($application);
      foreach ($this->tasks as $t) $t->invoke($application);
      foreach ($this->after as $t) $t->invoke($application);
      $this->reset();
    }
  }
}
?>
