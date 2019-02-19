Axel
====

A modular, extensible, fast, ultra-lightweight PHP autoloader with inbuilt caching

Design Philosophy
-----------------

Axel is fast but flexible.  Axel caches paths by automatically building an internal classmap (effectively just a map of file name => class name so the require statement becomes require `$classMap[$className]`). This class map can be cached between requests meaning most requests never have to go off looking for files.

Axel can be extended with modules that can locate classes that are not currently in the classmap. Once the module provides the location of the file for a given class, the path is cached and the module is never loaded again. Unless new classes are added to the project, Axel loads a single class which does an array key lookup to read the path.

This approach considerably faster and more flexible than composer as custom modules can be written and it doesn't matter how fast they are. Even if locating a file based on a class name is slow, it only happens once when the class is added. After that, Axel stores the result. This approach allows for things like case insensitive PSR-4 style loaders.


Usage
-----


To use Axel, firstly include axel.php into your project and create an instance of \Axel\Axel.php:

```php
require_once 'axel/axel.php';
$axel = new \Axel\Axel;
```

On its own, Axel does not do anything other than register an autoloader. Once it's been initialised you can tell it where to find your files by adding modules to it.

Note: The `Axel` instance is immutable and adding modules can be done via the `addModule` method:

```php
$axel = $axel->addModule($moduleName);
```


Once you have fully configured the autoloader you need to register it:

```php

require_once 'axel/axel.php';
$axel = new \Axel\Axel;
$axel = $axel->addModule($moduleName);
$axel->register();

```

Module: PSR4
--------------------

The PSR4 module allows you to map a namespace to a directory for example, if you have a project in `./lib/mylibrary/` you can map the namespace `MyLibrary` to the directory using:

```php
$axel = $axel->addModule(new \Axel\Module\PSR4('./lib/MyLibrary', '\\MyLibrary'));
```

Then, when the autoloader is triggered with

```php
new \MyLibrary\Foo();
```

Axel will now try to load the file `./lib/MyLibrary/Foo.php`


PSR4 can also be applied more generally so you don't have to create an instance of it for each library you're using. If all your libraries are in `./lib` you can map the root namespace to the dir and have everything work:

```php
$axel =  $axel->addModule(new \Axel\Module\PSR4('./lib/'));
```

This will add `./lib` as the root directory:


```php
new \MyLibrary\Foo();
new \MyOtherLibrary\Bar\Baz();
```

Will load the files `./lib/MyLibrary/Foo.php` and `./lib/MyOtherLibrary/Bar/Baz.php`


### Case-sensitivity

Because it's [generally a bad idea to make autoloaders case-sensitive](https://r.je/php-autoloaders-should-not-be-case-sensitive.html), Axel works in a case-insensitive way where possible.


```php
$axel = $axel->addModule(new \Axel\Module\PSR4('./lib/'));

new \MyLibrary\Foo();
```

Will look for the class in the file `./lib/MyLibrary/Foo.php`  however, if that file does not exist, it wil see if there's a lowercase version of the file `./lib/mylibrary/foo.php` and load that.

This is a best-of-both-worlds approach as it supports libraries using the widespread well intentioned but misguided PSR-4 standard that enforces case-sensitivity as well as libraries that use lowercase filenames.


Unlike composer, this allows you to register a PSR4 autoloader for any part of your project without having to write an additional autoloader:


```php
$axel = $axel->addModule(new \Axel\Module\PSR4('./Conf', '\\Conf'));
$axel = $axel->addModule(new \Axel\Module\PSR4('./Models', '\\Models'));
$axel = $axel->addModule(new \Axel\Module\PSR4('./Controllers', '\\OnlineShop\\Controllers'));

```

Module: Composer
---------------

Axel contains a module that allows reading `composer.json` to allow easy access to modules installed via  composer.

A drop-in replacement for composer's autoloader. It's faster and allows you to use Axel (Note: Currently only supports PSR-4, not classmap!) to load libraries installed via composer without using composer's autoload.php giving you a single autoloader for everything in the project.


Just load the module and tell Axel the path to the directory containing your `composer.json`. This will recursively load all vendor `composer.json` files and register them with the autoloader.

```php
$axel =  $axel->addModule(\Axel\Module\Composer($axel, './project'));

```


Writing a custom module
-----------------------

You can write your own autoload module by implementing the generic \Autoload\Module interface which looks like this:


```php
namespace Axel;
interface Module {
	public function locate($className);
}

```

Any class which implements this must provide the implementation for the `locate` method. The `locate` method is called by the autoloader and provides the class name to find. It should return the path of the file if it's part of the library or false if not. In its simplest terms a module could look like this:


```php
namespace MyLibrary;
class MyAutoloader implements \Axel\Module {
	public function locate($className) {
		if ($className == 'MyClass') return __DIR__ . DIRECTORY_SEPARATOR . 'MyClass.php';
		else if ($className == 'OtherClass') return __DIR__ . DIRECTORY_SEPARATOR . 'OtherClass.php';
		else return false;
	}

}
```

Caching
-------

Axel supports caching. When caching is enabled, `module::locate` is never called if the result has been retrieived previously. When using autoload.json, the file is only parsed if a file from the library has not been autoloaded before.

To enable caching, create a cache class that implements `\ArrayAccess`. Alternatively and for an example, see [SimpleCache](https://github.com/TomBZombie/SimpleCache/blob/master/SimpleCache.php).

To use a  cache with axel, intiate the cache class:

```php
require_once 'SimpleCache.php';
$simpleCache = new \SimpleCache\SimpleCache('./tmp');
```

Then pass the cache instance as Axel's constuctor argument:


```php
$axel = new \Axel\Axel($simpleCache);
```

Axel will now cache any paths behind the scenes. Any time a class is loaded its path is cached so it does not need to be located next time the script runs.

You can pass any instance that uses `\ArrayAccess` for your cache, behind the scenes this could use memcached, a database or any other caching format such as JSON or XML.

