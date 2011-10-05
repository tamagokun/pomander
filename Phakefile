<?php
set_include_path('lib');
require_once('Deploy.php');
global $deploy;
$deploy = new Deploy();
if( file_exists($deploy->config_path) )
  $deploy->config($deploy->config_path);
else
  warn("config","unable to locate config.yml");

task('test','environment', function($app)
{
  global $deploy;
  echo "testing...\n";
  puts($deploy->env->user);
  //var_dump($deploy->env);
});

task('app', function($app) { var_dump($app); });

task('deploy', function($app)
{
  global $env;
  if( $env == "staging\n" )
  {
    include('Net/SSH2.php');
    include('Crypt/RSA.php');
    define('NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX);
    $ssh = new Net_SSH2('stage1.mslideas.com');
    
    if( file_exists("/Users/mkruk/.ssh/id_rsa") )
    {
      $key = new Crypt_RSA();
      $pub = file_get_contents('/Users/mkruk/.ssh/id_rsa');
      $key->setPassword('');
      $result = $key->loadKey($pub);
      $login = $ssh->login('cap',$key);
    }else
    {
      $login = $ssh->login('cap');  
    }

    /*if(!$login)
    {
      echo $ssh->getLog();
      exit('Login failed');
    }*/
    echo $ssh->exec('cd /msl/php/acgmedemo && git pull');

    include('Net/SFTP.php');
    $sftp = new Net_SFTP('stage1.mslideas.com');
    if(!$sftp->login('cap',$key))
      exit('Login Failed');
    echo $sftp->pwd() . "\r\n";
  }
});

desc('Dump all args');
task('args', function($app) {
    echo "Arguments:\n";
    foreach ($app as $k => $v) echo "$k = $v\n";
});

desc('Initialises the database connection');
task('database', function() {
    echo "I am initialising the database...\n";
});

group('test', function() {
    
    // 'environment' dependency for this task is resolved locally to
    // task in same group. There is no 'database' task defined in this
    // group so it drops back to a search of the root group.
    desc('Run the unit tests');
    task('units', 'environment', ':environment', 'database', function() {
        echo "Running unit tests...\n";
    });
    
    // another level of nesting; application object is passed to all
    // executing tasks
    group('all', function() {
        desc('Run absolutely every test everywhere!');
        task('run', 'test:units', function($application) {
            echo "All tests complete! ($application)\n";
        });
    });

});

// duplicate group definitions are merged
group('test', function() {
    
    // duplicate task definitions are merged
    // (although the first description takes precedence when running with -T)
    desc("You won't see this description");
    task('units', function() {
        echo "Running a second batch of unit tests...\n";
    });
    
    // use ':environment' to refer to task in root group
    // we currently have no cyclic dependency checking, you have been warned.
    task('environment', ':environment', function() {
        echo "I am the inner environment. I should run second.\n";
    });
    
});

task('default', 'test:all:run');
?>
