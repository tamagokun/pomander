<?php
class Role
{
  public $targets,$current;

  public function __construct($targets)
  {
    $this->targets = $targets;
    $this->current = 0;
  }

  public function target()
  {
    if($this->has_target($this->current)) return $this->targets[$this->current];
    return null;
  }

  public function next()
  {
    if( $this->has_target($this->current+1))
      $this->current++;
    else
      return false;
    return $this->target();
  }

  public function has_target($index)
  {
    return array_key_exists($index,$this->targets);  
  }
}
?>
