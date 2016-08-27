infuse/migrations
=================

[![Build Status](https://travis-ci.org/infusephp/migrations.svg?branch=master&style=flat)](https://travis-ci.org/infusephp/migrations)
[![Coverage Status](https://coveralls.io/repos/infusephp/migrations/badge.svg?style=flat)](https://coveralls.io/r/infusephp/migrations)
[![Latest Stable Version](https://poser.pugx.org/infuse/migrations/v/stable.svg?style=flat)](https://packagist.org/packages/infuse/migrations)
[![Total Downloads](https://poser.pugx.org/infuse/migrations/downloads.svg?style=flat)](https://packagist.org/packages/infuse/migrations)
[![HHVM Status](http://hhvm.h4cc.de/badge/infuse/migrations.svg?style=flat)](http://hhvm.h4cc.de/package/infuse/migrations)

Database migrations module for Infuse Framework. Built on [Phinx](https://github.com/robmorgan/phinx).

## Installation

1. Install the package with [composer](http://getcomposer.org):

		composer require infuse/migrations

2. Add the console command to `console.commands` in your app's configuration:

	```php
	'console' => [
		// ...
		'commands' => [
			// ...
			'Infuse\Migrations\Console\MigrateCommand'
		]
	]
	```

3. Add your migrations to `modules.migrations` in your app's configuration:

	```php
	'modules' => [
		// ..
		'migrations' => [
			'auth',
			'users',
			// ...
		]
	]
	```