<?php
class Environment
{
  public $name;
  private $config,$shell;

  public function __construct($env_name,$args=null)
  {
    $this->name = $env_name;
    $this->config = (array) $args;
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

  public function connect()
  {
    include('Net/SSH2.php');
    include('Crypt/RSA.php');
    define('NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX);
    //change to :app or :db
    $this->shell = new Net_SSH2('stage1.mslideas.com');
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

    puts($this->shell->exec("ls -al"));
  }

  public function exec($cmd)
  {
    if($this->shell)
      return $this->shell->exec($cmd);
    else
      return shell_exec($cmd);  
  }

}
?>
