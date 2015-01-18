Axel
====

A modular, extensible, fast, ultra-lightweight PHP autoloader with inbuilt caching

Usage
-----


To use Axel, firstly include axel.php into your project and create an instance of \Axel\Axel.php:

```php
require_once 'axel/axel.php';
$axel = new \Axel\Axel;
```

On its own, Axel does not do anything other than register an autoloader. Once it's been initialised you can tell it where to find your files by adding modules to it.

Module: NamespaceMap
--------------------

The NamespaceMap module allows you to map a namespace to a directory for example, if you have a project in `./lib/mylibrary/` you can map the namespace `MyLibrary` to the directory using:

```php
$axel->addModule(new \Axel\Module\NamespaceMap('./lib/MyLibrary', '\\MyLibrary'));
```

Then, when the autoloader is triggered with

```php
new \MyLibrary\Foo();
```

Axel will now try to load the file `./lib/MyLibrary/Foo.php`


NamespaceMap can also be applied more generally so you don't have to create an instance of it for each library you're using. If all your libraries are in `./lib` you can map the root namespace to the dir and have everything work:

```php
$axel->addModule(new \Axel\Module\NamespaceMap('./lib/'));
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
$axel->addModule(new \Axel\Module\NamespaceMap('./lib/'));

new \MyLibrary\Foo();
```

Will look for the class in the file `./lib/MyLibrary/Foo.php`  however, if that file does not exist, it wil see if there's a lowercase version of the file `./lib/mylibrary/foo.php` and load that.

This is a best-of-both-worlds approach as it supports libraries using the widespread well intentioned but misguided PSR-4 standard that enforces case-sensitivtity as well as libraries that use lowercase filenames.

#### Performance consderations 

This presents a potential performance problem if Axel has look for files in two places, change strings to lowercase, etc. However, because Axel supports caching (see the section later on this page) this is not an issue.


 
Module: Library
---------------

One of the biggest issues with PSR-4 and its predecessor PSR-0 is that it forces library authors to structure their files in compliance with the autoloader. This is backwards, and instead a better way of achieving this is to allow a library to tell the autoloader where to find its files (as opposed to the autoloader telling the library where to place its files)

The difference is subtle but consider taking a library written for PHP5.2 before namespaces were created, to make it work with a PSR-4 autoloader you must manually alter each file adding a namespace and proably moving the files around. This creates work for you, but more importantly you have to do it all again if the non-psr-compliant library is ever updated and you want the new features!

Axel solves this flaw that is inherent to the PSR-4 standard by allowing the library to inform the autoloader how to find its files.

Any library can provide an autoload.json which allows the library to extend the behvaiour of the autoloader.

### autoload.json 

The autoload.json looks like this:

```
{
 'include': ['myautoloader.php'],
 'modules': {
    'Axel\\Module\\NamespaceMap': ['Foo\\bar', '\\ThisLibrary\\Foo\\Bar'],
    'MyLibrary\\MyAutoLoader' : ['constructor', 'args', 'for', 'autoloader', 'in', 'myautoloader.php']
   }
 }
```


The autoload.json has two top level elements: 

`include` which is an array of files which are automatically included.  

`modules` which is a list of modules to register with the autoloader. This can include NamespaceMap and any custom modules that have been loaded in `include`


Once you've created autoload.json you need to tell Axel to look for it. This is done by registering the module `Axel\Module\Library` with the autoloader:


```php
$axel->addModule(new \Axel\Module\Library($axel, './lib');
```

The first parameter to the Library constructor is the `$axel` instance. This is required because the Library module needs to register other modules with the autoloader.

The second parameter is the root directory. In this case './lib'

Once this is set up, the code:

```php
new MyLibrary\Foo();
```

Will cause the Library module to look for `./lib/MyLibrary/autoload.json` and load it if it exists. This will then include any required files from the `include` section and register any autload modules from the `modules` section.


### Writing a custom module

You can write your own autoload module by implementing the generic \Autoload\Module interface which looks like this:


```php
namespace Autoload;
interface Module {
	public function locate($className);
}

```

Any class which implements this must provide the implementation for the `locate` method. The `locate` method is called by the autoloader and provides the class name to find. It should return the path of the file if it's part of the library or false if not. In its simplest terms a module could look like this:


```php
namespace MyLibrary;
class MyAutoloader implements \Autoload\Module {
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

To enable caching, create a cache class that implements \ArrayAccess. Alternatively and for an example, see [SimpleCache](https://github.com/TomBZombie/SimpleCache/blob/master/SimpleCache.php).

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

You can pass any instance that uses \ArrayAccess for your cache, behind the scenes this could use memcached, a database or any other caching format such as JSON or XML.

