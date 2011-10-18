<?php
class Environment
{
  public $name,$target,$scm,$adapter;
  private $config,$shell,$mysql;
  private $roles;

  public function __construct($env_name,$args=null)
  {
    $this->name = $env_name;
    $this->config = (array) $args;
    $this->roles = array("app"=>null,"db"=>null);
    $this->init_scm_adapter();
    if(!isset($this->deploy_to) || empty($this->deploy_to))
      $this->deploy_to = ".";
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
    require_once("Role.php");
    if(!$this->$key) return false;
    if( !$this->roles[$key] )
    {
      $this->roles[$key] = new Role($this->$key);
      return $this->update_target($this->roles[$key]->target());
    }else
    {
      return $this->update_target($this->roles[$key]->next());
    }
  }

  public function next_role($key)
  {
    if( !$this->roles[$key]) return false;
    return $this->roles[$key]->has_target($this->roles[$key]->current+1);
  }

  public function connect()
  {
    if( !isset($this->target) ) return false;
    set_include_path(POMANDER_PATH."phpseclib");
    include_once('Net/SSH2.php');
    include_once('Crypt/RSA.php');
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

  public function put($what,$where)
  {
    if($this->target)
      $cmd = "rsync -avuzPO --quiet $what {$this->user}@{$this->target}:$where";
    else
      $cmd = "cp $what $where";
    return shell_exec($cmd);
  }

  public function get($what,$where)
  {
    if($this->target)
      $cmd = "rsync -avuzPO --quiet {$this->user}@{$this->target}:$what $where";
    else
      $cmd = "cp $what $where";
    return shell_exec($cmd);
  }

  public function query($query,$select_db)
  {
    if(!$this->mysql)
      if(!$this->db_connect()) return false;
    if( $select_db )
      mysql_select_db($this->wordpress["db"],$this->mysql);
    mysql_query($query,$this->mysql);
  }

  private function update_target($target)
  {
    if( !$target ) return false;
    if( $this->target == $target ) return true;
    if( $this->shell )
      $this->shell = null;
    $this->target = $target;
    info("target",$this->target);
    return true;
  }

  private function init_scm_adapter()
  {
    require_once("pomander/Scm.php");
    require_once_dir("pomander/Scm/*.php");
    $this->config["scm"] = (!isset($this->config["scm"]))? "Git" : ucwords(strtolower($this->config["scm"]));
    if( !$this->scm = new $this->config["scm"]($this->repository) )
      warn("scm","There is no recipe for {$this->config["scm"]}, perhaps create your own?");
    require_once("pomander/Db.php");
    require_once_dir("pomander/Db/*.php");
    $this->config["adapter"] = (!isset($this->config["adapter"]))? "Mysql" : ucwords(strtolower($this->config["adapter"]));
    if( !$this->adapter = new $this->config["adapter"]($this->wordpress) )
      warn("db","There is no recipe for {$this->config["adapter"]}, perhaps create your own?");
  }

  private function db_connect()
  {
    $this->mysql = @mysql_connect($this->wordpress["db_host"],$this->wordpress["db_user"],$this->wordpress["db_password"]);
    if( !$this->mysql )
      warn("mysql","there was a problem establishing a connection");
    return $this->mysql;
  }

}
?>
