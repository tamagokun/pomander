<?php
require_once("Environment.php");

class Deploy
{
  public $config_path,$env;
  private $config,$default_env;

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
    $this->use_default();
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

  private function use_default()
  {
    task("environment",$this->default_env);
  }
}

//core tasks
task("environment",function($app) {
  
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
?>
