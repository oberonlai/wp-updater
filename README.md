# WP Updater v1.0

> Simple WordPress Class for self host plugin updating server from [How to Configure Self-Hosted Updates for Your Private Plugins](https://rudrastyh.com/wordpress/self-hosted-plugin-update.html) 

## Requirements

* PHP >=7.2
* [Composer](https://getcomposer.org/)
* [WordPress](https://wordpress.org) >=5.4

## Installation

#### Install with composer

Run the following in your terminal to install with [Composer](https://getcomposer.org/).

```
$ composer require oberonlai/wp-updater
```

WP Metabox [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading and can be used with the Composer's autoloader. Below is a basic example of getting started, though your setup may be different depending on how you are using Composer.

```php
require __DIR__ . '/vendor/autoload.php';

use ODS\Updater;

$options = array( ... );

$books = new Updater( $options );

```

See Composer's [basic usage](https://getcomposer.org/doc/01-basic-usage.md#autoloading) guide for details on working with Composer and autoloading.

## Basic Usage

Below is a basic example of setting up a plugin updater.

```php
// Require the Composer autoloader.
require __DIR__ . '/vendor/autoload.php';

// Import PostTypes.
use ODS\Updater;

```

## Usage

To create a updater, first instantiate an instance of `Updater`. The class takes one argument, which is an associative array.

```php
$updater = new Updater( array(
	'plugin_slug' => 'my-plugin',
	'version'     => '1.0.0',
	'json_url'    => 'https://mydomain.com/my-plugin.json',
));
```

## Preparations
You will need a hosting space to store the information file of the plugin and the zip file of the new version of the plugin. You can use space such as Dropbox or Google Drive, or you can place it on your own server. My top recommendation is to host it on GitHub, as it allows integration with version control processes.

## my-plugin.json

This is an example of a JSON-formatted file for the plugin information. It includes the following fields:

```JSON
{
	"name": "My Plugin",
	"slug": "my-plugin",
	"author": "<a href='https://mydomain.com/my-plugin/'>Author</a>",
	"author_profile": "https://mydomain.com",
	"version": "1.0.1",
	"download_url": "https://mydomain.com/my-plugin.zip",
	"requires": "5.6",
	"tested": "6.2.2",
	"requires_php": "7.4",
	"added": "2021-04-05 00:00:00",
	"last_updated": "2023-06-29 00:00:00",
	"homepage": "https://mydoamin.com",
	"sections": {
	  "description": "My plugin description",
	  "installation": "My plugin installation",
	  "changelog": "<p>v1.0.1</p><p>Change log</p>"
	},
	"banners": {
	  "low": "https://mydomain.com/my-plugin-banner-low.jpg",
	  "high": "https://mydomain.com/my-plugin-banner-high.jpg"
	}
}
```

If you want to push a notification for a new update, simply increment the version number in the plugin. The download_url indicates the location to download the new version of the plugin.

## Hooks - ods_updater_after_purge

Allow plugin developers to perform additional tasks after the plugin update is completed, such as updating database tables, displaying notification alerts, and other behaviors. These tasks can be handled through this hook point.

```php
add_action('ods_updater_after_purge', function( $upgrader, $options ){
	// do stuff when plugin updated.
});
```