<?php
require_once("Environment.php");

class Deploy
{
  static $home;
  public $config_path,$default_env,$env;
  private $config;

  public function __construct()
  {
    $this->default_env = "development";
    $this->config_path = getcwd() . "/config.yml";   
  }
  
  public static function colorize($text,$color)
  {
    #31 red
    #32 green
    #35 purple
    return "\033[{$color}m{$text}\033[0m";
  }

  public function config($yaml)
  {
    require_once("spyc.php");
    $this->config = Spyc::YAMLLoad($yaml);
    $this->load_environments();
  }

  private function load_environments()
  {
    foreach($this->config as $env_name=>$environment)
    {
      $env = new Environment($env_name,$environment);
      task($env_name, function($app) use($env) {
        global $deploy;
        info("environment",$env->name);
        $deploy->env = $env;
      });
    }
  }

}

//core tasks
task("environment",function($app) {
  global $deploy;
  if(!$deploy->env)
    $app->invoke($deploy->default_env);
  if($deploy->env->name != "development")
    $deploy->env->connect(); 
});

//utils
function info($status,$msg)
{
  puts(" * ".Deploy::colorize("info ",32).Deploy::colorize($status." ",35).$msg);
}

function warn($status,$msg)
{
  puts(" * ".Deploy::colorize("warn ",31).Deploy::colorize($status." ",35).$msg);
}

function puts($text)
{
  echo $text."\n";  
}

function home()
{
  if(!Deploy::$home)
  {
    Deploy::$home = trim(shell_exec("cd ~ && pwd"),"\r\n");
    shell_exec("cd ".getcwd());
  }
  return Deploy::$home;
}

function run()
{
  global $deploy;
  $cmd = implode(" && ",flatten(func_get_args()));
  echo $deploy->env->exec($cmd);
}

function flatten($array)
{
  return (array) new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
}

?>
