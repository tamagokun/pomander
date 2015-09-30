[Pomander](http://tamagokun.github.io/pomander/)
=======

![](https://api.travis-ci.org/tamagokun/pomander.png?branch=master)
[![Latest Stable Version](https://poser.pugx.org/pomander/pomander/v/stable.png)](https://packagist.org/packages/pomander/pomander)

[![Gitter chat](https://badges.gitter.im/tamagokun/pomander.png)](https://gitter.im/tamagokun/pomander)

A light-weight flexible deployment tool for deploying web applications. This project was inspired by [Capistrano](https://github.com/capistrano/capistrano) and [Vlad the Deployer](http://rubyhitsquad.com/Vlad_the_Deployer.html), as well as being built on top of [Phake](https://github.com/jaz303/phake), a [Rake](http://rake.rubyforge.org/) clone.

This project came out of the need for a way to deploy Wordpress sites to multiple environments easily and without firing up FTP clients, etc. What started as a simple Rakefile, quickly grew into much more, and has been finally abstracted and ported to PHP to be able to fully integrate tasks with your application.

Getting Started
---------------

Make sure you have [composer](http://getcomposer.org/) installed:

```bash
$ curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

[_Need help installing composer?_](http://getcomposer.org/doc/00-intro.md#installation-nix)

Installation
------------

```bash
$ composer global require pomander/pomander:@stable
```

If you haven't added composer's global bin folder to your `$PATH`, better do that now:

```bash
$ echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
```

_Substitute .bashrc with whatever you use._

Setting up a project
--------------------

```bash
$ pom init
```

Refer to the [documentation](http://tamagokun.github.io/pomander/) for a full list of commands and references for configuring environments.


Plugins
-------

* [pomander-wordpress](https://github.com/tamagokun/pomander-wordpress)
* [pomander-symfony2](https://github.com/leopoiroux/pomander-symfony2)
