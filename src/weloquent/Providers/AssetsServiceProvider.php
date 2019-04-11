<?php namespace Weloquent\Providers;

use Brain\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Weloquent\Core\Http\Router;

/**
 * AssetsServiceProvider
 *
 * @author Bruno Barros  <bruno@brunobarros.com>
 * @copyright    Copyright (c) 2014 Bruno Barros
 */
class AssetsServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        // TODO: FIXME
        // $app['assets'] is Unset
        // Application will not load without it.
        //
        // Argument 1 ($app['assets']) passed to Illuminate\Routing\Router::__construct() must implement interface Illuminate\Contracts\Events\Dispatcher,

        // STILL WRONG - BUT LESS
        $this->app->bindShared('assets', function ()
        {
            return new Dispatcher;
        });

        $this->app['router'] = $this->app->share(function($app)
		{
			$router = new Router($app['assets'], $app);

			// If the current application environment is "testing", we will disable the
			// routing filters, since they can be tested independently of the routes
			// and just get in the way of our typical controller testing concerns.
			if ($app['env'] == 'testing')
			{
				$router->disableFilters();
			}

			return $router;
		});

        $this->app->bindShared('weloquent.assets', function ($app)
        {
            return Container::instance()->get('occipital.api');
        });
	}
}
