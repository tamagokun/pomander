Pomander
=======

A light-weight flexible deployment tool for deploying web applications. This project was inspired by [Capistrano](https://github.com/capistrano/capistrano) and [Vlad the Deployer](http://rubyhitsquad.com/Vlad_the_Deployer.html), as well as being built on top of [Phake](https://github.com/jaz303/phake), a [Rake](http://rake.rubyforge.org/) clone.

This project came out of the need for a way to deploy Wordpress sites to multiple environments easily and without firing up FTP clients, etc. What started as a simple Rakefile, quickly grew into much more, and has been finally abstracted and ported to PHP to be able to fully integrate tasks with your application.

Installation
------------

Requirements:

* PHP 5.3.1+
* [composer](http://getcomposer.org/)

```json
{
	"require": {
		"pomander/pomander": "dev-master"
	}
}
```

```
$ composer install
```

Usage
-----

### Set up your project for use with Pomander

    $ cd myproject
    $ pomify

This will give you `Pomfile` where you can configure plugins, and it will also create a default deployment configuration.
    
Use `pom -T` to see your available tasks.
    
### Configure environments

To configure an environment, just drop a `.yml` or `.php` file named the environment you want to create in the deploy folder. `pom config` will create a development.yml file to get you going if you don't already have one. You can create as many environments as you want.

Configuration reference:

<dl>
<dt>url</dt>
<dd>Application URL. Used primarily for databse migration and isn't always needed.</dd>
<dt>user</dt>
<dd>SSH user for performing remote tasks.</dd>
<dt>repository</dt>
<dd>Repository url.</dd>
<dt>revision</dt>
<dd>Revision/branch to deploy. _Default: origin/master, trunk_</dd>
<dt>scm</dt>
<dd>SCM to use. Currently supports svn and git. _Default: git_</dd>
<dt>releases</dt>
<dd>Use current/releases/shared structure. (true|false|number of releases to keep) _Default: false_</dd>
<dt>adapter</dt>
<dd>Data adapter to use for databases. Currently supports MySQL. _Default: mysql_</dd>
<dt>remote\_cache</dt>
<dd>Cache repository for faster deploys. (true|false) _Default: true when releases isn't false_</dd>
<dt>deploy\_to</dt>
<dd>Application is deployed here. _Default: cwd_</dd>
<dt>backup</dt>
<dd>Perform backup (database) on deployments. (true|false). _Default: false_</dd>
<dt>umask</dt>
<dd>Umask to use for remote tasks. _Default: 002_</dd>
<dt>rsync_cmd</dt>
<dd>Command to use for file syncing. _Default: rsync_</dd>
<dt>rsync_flags</dt>
<dd>Extra flags to use for file syncing. _Default: -avuzPO --quiet_</dd>
<dt>app</dt>
<dd>String or Array of application hosts to deploy to.</dd>
<dt>db</dt>
<dd>String or Array of database hosts to deploy to.</dd>
</dl>

__PHP configurations look like this:__

```php
<?php
	$env->user('deploy')
	    ->repository('git@github.com:tamagokun/pomander.git')
			->deploy_to('/var/www/html')
```

### Deploying

    pom deploy:setup # Just run this the first time
    
    # All subsequent deployments use pom deploy (deploy defaults to deploy:update)
    pom deploy

Tasks
-----

```
deploy:setup      # Creates deploy_to folder, and checks out code.
deploy:cold       # Alias for deploy:setup
delpoy:update     # Updates code to current revision/branch.
config            # Attempts to create a default `development.yml` file.
```

### Custom Tasks

Feel free to modify these existing tasks, as well as create your own!

e.g.

```php
<?php
$pom = new \Pomander\Builder();
/* plugins
 * $pom->load('pomander/wordpress'); */
$pom->run();
```

```php
<?php

task('my custom task',function($app) {
  info("my task","Hello, World!");
});

after('deploy:update', function($app) {
  warn("pomander","You can use after() / before() to customize tasks");
});
```

Plugins
-------

* [pomander-wordpress](https://github.com/tamagokun/pomander-wordpress)
