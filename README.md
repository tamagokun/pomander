Pomander
=======

Stuff will go here!

Installation
------------

Requirements:

* PHP 5.3.1+
* [phark](https://github.com/lox/phark)

Until phark is more developed to allow installation of remote packages, this is how we do it:

    git clone git://github.com/tamagokun/pomander.git
    cd pomander && bin/phark-install
    
Don't have phark? Here's a similar technique to install it:

    git clone git://github.com/lox/phark.git
    cd phark && make

Usage
-----

### Set up your project for use with Pomander

    cd myproject
    pomify					# Creates 'Pomfile' any additional plugins/custom tasks go here.
    pom config 			# This creates deploy/development.yml.
    
Use `pom -T` to see your available tasks.
    
### Configure environments

Pomander uses YAML files to configure environments. `pom setup` will create a development.yml file to get you going. You can create as many environments as you want.

Configuration reference:

    url: # URL of application. Used primarily for database migration, and may not be needed.
    user: # User for performing remote tasks.
    repository: # Repository for application.
    revision: # Desired revision/branch to be deployed.
    scm: # SCM to use. Currently supports svn and git. Default: git
    deploy_to: # Path to deploy to.
    backup: # Perform database backups on deployments. (true|false). Default: false
    app: # List of application end-points for running deployment tasks.
      - node1.myapp.com
      - node2.myapp.com
    db:	# List of database end-points for running database tasks.
      - db1.myapp.com

### Deploying

    pom deploy:setup # Just run this the first time
    
    # All subsequent deployments use pom deploy (deploy defaults to deploy:update)
    pom deploy

Tasks
-----

`deploy:setup` - Creates deploy_to folder, and checks out code.

`delpoy:update` - Updates code to current revision/branch.

`deployed` - Tells you what revision/branch is currently deployed.

`config` - Attempts to create a default `development.yml` file.

Plugins
-------

* [pomander-wordpress](https://github.com/tamagokun/pomander-wordpress)
