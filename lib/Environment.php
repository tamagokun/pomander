<?php
class Environment
{
  public $name,$target,$scm;
  private $config,$shell,$current_role,$current_role_key;

  public function __construct($env_name,$args=null)
  {
    $this->name = $env_name;
    $this->config = (array) $args;
    $this->init_scm();
  }

  public function __get($prop)
  {
    if( array_key_exists($prop, $this->config))
      return $this->config[$prop];
    return null;
  }

  public function __isset($prop)
  {
    return isset($this->config[$prop]);
  }

  public function __set($prop,$value)
  {
    $this->config[$prop] = $value;
  }

  public function role($key)
  {
    if(!$this->$key) return false;
    if( !$this->current_role )
    {
      $this->current_role = $this->$key;
      $this->target = $this->current_role[0];
      return $this->new_target();
    }else
    {
      $index = array_search($this->target,$this->current_role);
      if( isset($this->current_role[$index+1]))
      {
        $this->target = $this->current_role[$index+1];
        return $this->new_target();
      }
      else
        $this->current_role = false;
    }
  }

  public function next_role()
  {
    if(!isset($this->target) || !isset($this->current_role)) return false;
    $index = array_search($this->target,$this->current_role);
    return isset($this->current_role[$index+1]);
  }

  public function connect()
  {
    if( !isset($this->target) ) return false;
    include_once('Net/SSH2.php');
    include_once('Crypt/RSA.php');
    define('NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX);
    $this->shell = new Net_SSH2($this->target);
    $key_path = home()."/.ssh/id_rsa";
    if( file_exists($key_path) )
    {
      $key = new Crypt_RSA();
      $key_status = $key->loadKey(file_get_contents($key_path));
      if(!$key_status) warn("ssh","Unable to load RSA key");
    }else
    {
      if( isset($this->password) )
        $key = $this->password;
    }

    if(!$this->shell->login($this->user,$key))
      warn("ssh","Login failed");
  }

  public function exec($cmd)
  {
    if($this->target && !$this->shell)
      $this->connect();
    if($this->shell)
      return $this->shell->exec($cmd);
    else
      return shell_exec($cmd);
  }

  private function new_target()
  {
    info("target",$this->target);
    return true;
  }

  private function init_scm()
  {
    require_once("Scm.php");
    foreach(glob("lib/Scm/*.php") as $file) require_once "Scm/".basename($file);
    $this->config["scm"] = (!isset($this->config["scm"]))? "Git" : ucwords(strtolower($this->config["scm"]));
    if( !$this->scm = new $this->config["scm"]($this->repository) )
      warn("scm","There is no recipe for {$this->config["scm"]}, perhaps create your own?");  
  }

}
?>
