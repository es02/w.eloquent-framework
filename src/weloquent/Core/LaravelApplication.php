<?php

use Weloquent\Core\Application;
use Weloquent\Core\Http\Request;
use Weloquent\Facades\Route;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

// If this file is called directly, abort.
if (!defined('WPINC'))
{
	die;
}

/*
|--------------------------------------------------------------------------
| Check Extensions
|--------------------------------------------------------------------------
|
| Laravel requires a few extensions to function. Here we will check the
| loaded extensions to make sure they are present. If not we'll just
| bail from here. Otherwise, Composer will crazily fall back code.
|
*/

if ( ! extension_loaded('openssl'))
{
	echo 'OpenSSL PHP extension required.'.PHP_EOL;

	exit(1);
}


/*
|--------------------------------------------------------------------------
| Setup Patchwork UTF-8 Handling
|--------------------------------------------------------------------------
|
| The Patchwork library provides solid handling of UTF-8 strings as well
| as provides replacements for all mb_* and iconv type functions that
| are not available by default in PHP. We'll setup this stuff here.
|
| Should be removed for Laravel 5
|
*/

// Patchwork\Utf8\Bootup::initMbstring();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new \Weloquent\Core\Application(dirname(ABSPATH).'/app/');

/**
 * -----------------------------------------------------
 * Set my custom request class
 * Not used in Laravel 5
 * -----------------------------------------------------
 */
// Application::requestClass('Weloquent\Core\Http\Request');

/*
|--------------------------------------------------------------------------
| Detect The Application Environment
|--------------------------------------------------------------------------
|
| Laravel takes a dead simple approach to your application environments
| so you can just specify a machine name for the host that matches a
| given environment, then we will automatically detect it for you.
|
*/

$objEnv     = new Weloquent\Config\Environment(dirname(ABSPATH));
$env        = $objEnv->which();
$app['env'] = $env;
/*
|--------------------------------------------------------------------------
| Bind Paths
|--------------------------------------------------------------------------
|
| Here we are binding the paths configured in paths.php to the app. You
| should not be changing these here. If you need to change these you
| may do so within the paths.php file and they will be bound here.
|
| Removed in Laravel 5
|
*/
//$app->bindInstallPaths(require SRC_PATH . '/bootstrap/paths.php');

/*
|--------------------------------------------------------------------------
| Bind The Application In The Container
|--------------------------------------------------------------------------
|
| This may look strange, but we actually want to bind the app into itself
| in case we need to Facade test an application. This will allow us to
| resolve the "app" key out of this container for this app's facade.
|
*/

$app->instance('app', $app);

/*
|--------------------------------------------------------------------------
| Check For The Test Environment
|--------------------------------------------------------------------------
|
| If the "unitTesting" variable is set, it means we are running the unit
| tests for the application and should override this environment here
| so we use the right configuration. The flag gets set by TestCase.
|
*/

if (isset($unitTesting))
{
	$app['env'] = $env = $testEnvironment;
}

/*
|--------------------------------------------------------------------------
| Load The Illuminate Facades
|--------------------------------------------------------------------------
|
| The facades provide a terser static interface over the various parts
| of the application, allowing their methods to be accessed through
| a mixtures of magic methods and facade derivatives. It's slick.
|
*/

Facade::clearResolvedInstances();

Facade::setFacadeApplication($app);
/*
|--------------------------------------------------------------------------
| Register Facade Aliases To Full Classes
|--------------------------------------------------------------------------
|
| By default, we use short keys in the container for each of the core
| pieces of the framework. Here we will register the aliases for a
| list of all of the fully qualified class names making DI easy.
|
*/
$app->registerCoreContainerAliases();

/*
|--------------------------------------------------------------------------
| Register The Configuration Repository
|--------------------------------------------------------------------------
|
| The configuration repository is used to lazily load in the options for
| this application from the configuration files. The files are easily
| separated by their concerns so they do not become really crowded.
|
*/
// use Illuminate\Filesystem\Filesystem;

// $app->instance('config', $config = new Config(
//
// 	new FileLoader(new Filesystem, $app['path'] . '/config'), $env
//
// ));

$config = new LoadConfiguration();
$config->Bootstrap($app);

$app->instance('path.config', app()->basePath() . DIRECTORY_SEPARATOR . 'config');
$app->instance('path.storage', app()->basePath() . DIRECTORY_SEPARATOR . 'storage');
$app->instance('path.theme', app()->basePath(). '/../src/themes/' . APP_THEME);

/*
|--------------------------------------------------------------------------
| Register Application Exception Handling
|--------------------------------------------------------------------------
|
| We will go ahead and register the application exception handling here
| which will provide a great output of exception details and a stack
| trace in the case of exceptions while an application is running.
|
|
| This changed with Laravel 5 and I'm not currently sure how to bootstrap it
| so we'll leave it out for now
*/

// $app->startExceptionHandling();
ini_set('display_errors', 'On');
if (!$app->config->get('app.debug')) {
// if ($env != 'testing') {
    // We *should* be able to define('WELOQUENT_TEST_ENV', true);
    // in our main application and have it switch $env to testing however
    // this doesn't seem to be the case;
    // Probably a good thing however given that should break WP

    ini_set('display_errors', 'Off');
}

/*
|--------------------------------------------------------------------------
| Set The Default Timezone
|--------------------------------------------------------------------------
|
| Here we will set the default timezone for PHP. PHP is notoriously mean
| if the timezone is not explicitly set. This will be used by each of
| the PHP date and date-time functions throughout the application.
|
*/

$config = $app['config']['app'];

date_default_timezone_set($config['timezone']);

/*
|--------------------------------------------------------------------------
| Register The Alias Loader
|--------------------------------------------------------------------------
|
| The alias loader is responsible for lazy loading the class aliases setup
| for the application. We will only register it if the "config" service
| is bound in the application since it contains the alias definitions.
|
*/

$aliases = $config['aliases'];

AliasLoader::getInstance($aliases)->register();

/*
|--------------------------------------------------------------------------
| Enable HTTP Method Override
|--------------------------------------------------------------------------
|
| Next we will tell the request class to allow HTTP method overriding
| since we use this to simulate PUT and DELETE requests from forms
| as they are not currently supported by plain HTML form setups.
|
*/

Request::enableHttpMethodParameterOverride();

/*
|--------------------------------------------------------------------------
| Register The Core Service Providers
|--------------------------------------------------------------------------
|
| The Illuminate core service providers register all of the core pieces
| of the Illuminate framework including session, caching, encryption
| and more. It's simply a convenient wrapper for the registration.
|
*/

$app->registerConfiguredProviders();

$app->booted(function () use ($app, $env)
{

	/**
	 * --------------------------------------------------------------------------
	 * After load the application
	 * Load the logs and errors handlers
	 * --------------------------------------------------------------------------
	 *
	 * The start scripts gives this application the opportunity to override
	 * any of the existing IoC bindings, as well as register its own new
	 * bindings for things like repositories, etc. We'll load it here.
	 */
	$path = $app['path'].DS.'config'.DS.'logs.php';

	if (file_exists($path))
	{
		require $path;
	}
});

/**
 * This technically should happen in App.php but that isn't actually getting
 * called anywhere so, meh.
 */

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->bind(\Illuminate\Contracts\Routing\UrlGenerator::class, function ($app) {
    $routes = $app['router']->getRoutes();
    $request = $app['url']->getRequest();
    return new \Illuminate\Routing\UrlGenerator($routes, $request);
});

// $app->run();

// Kernel is instantiated elsewhere now - this will just crash laravel

// $kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// $response = $kernel->handle(
//     $request = Illuminate\Http\Request::capture()
// );
//
// $response->send();
//
// $kernel->terminate($request, $response);

return $app;
