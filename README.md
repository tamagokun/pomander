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

Pomander uses YAML files to configure environments. `pom config` will create a development.yml file to get you going if you don't already have one. You can create as many environments as you want.

Configuration reference:

url
: Application URL. Used primarily for databse migration and isn't always needed.
user
: SSH user for performing remote tasks.
repository
: Repository url.
revision
: Revision/branch to deploy. _Default: origin/master, trunk_
scm
: SCM to use. Currently supports svn and git. _Default: git_
releases
: Use current/releases/shared structure. (true|false|number of releases to keep) _Default: false_
adapter
: Data adapter to use for databases. Currently supports MySQL. _Default: mysql_
remote\_cache
: Cache repository for faster deploys. (true|false) _Default: true when releases isn't false_
deploy\_to
: Application is deployed here. _Default: cwd_
backup
: Perform backup (database) on deployments. (true|false). _Default: false_
umask
: Umask to use for remote tasks. _Default: 002_
rsync_cmd
: Command to use for file syncing. _Default: rsync_
rsync_flags
: Extra flags to use for file syncing. _Default: -avuzPO --quiet_
app
: String or Array of application hosts to deploy to.
db
: String or Array of database hosts to deploy to.

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
