Pomander
=======

A light-weight flexible deployment tool for deploying web applications. This project was inspired by [Capistrano](https://github.com/capistrano/capistrano) and [Vlad the Deployer](http://rubyhitsquad.com/Vlad_the_Deployer.html), as well as being built on top of [Phake](https://github.com/jaz303/phake), a [Rake](http://rake.rubyforge.org/) clone.

This project came out of the need for a way to deploy Wordpress sites to multiple environments easily and without firing up FTP clients, etc. What started as a simple Rakefile, quickly grew into much more, and has been finally abstracted and ported to PHP to be able to fully integrate tasks with your application.

Installation
------------

Requirements:

* PHP 5.3.1+
* [phark](https://github.com/lox/phark)

Until phark is more developed to allow installation of remote packages, this is how we do it:

    $ git clone git://github.com/tamagokun/pomander.git && cd pomander
    $ phark install .
    
Don't have phark? Here's an easy way to install it:

    $ git clone git://github.com/lox/phark.git
    $ cd phark && make

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

```yaml
url:                    # Application URL. Used primarily for database migration and may not be needed.
user:                   # User for performing remote tasks.
repository:             # Repository for application.
revision:               # Desired revision/branch to be deployed.
scm:                    # SCM to use. Currently supports svn and git. Default: git
deploy_to:              # Path to deploy to.
backup:                 # Perform database backups on deployments. (true|false). Default: false
app:                    # List of application end-points for running deployment tasks.
  - node1.myapp.com
  - node2.myapp.com
db:	                    # List of database end-points for running database tasks.
  - db1.myapp.com
```

### Deploying

    pom deploy:setup # Just run this the first time
    
    # All subsequent deployments use pom deploy (deploy defaults to deploy:update)
    pom deploy

Tasks
-----

```
deploy:setup      # Creates deploy_to folder, and checks out code.
delpoy:update     # Updates code to current revision/branch.
deployed          #  Tells you what revision/branch is currently deployed.
config            # Attempts to create a default `development.yml` file.
```

### Custom Tasks

Feel free to modify these existing tasks, as well as create your own!

e.g.

```php
<?php
require_once('pomander/lib/pomander/pomander.php');
//plugins go here
include('./lib/tasks/mytasks.php');
if(has_environments()) config();
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
