<?php namespace Weloquent\Providers;

use Brain\Container;
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
        // This isn't getting set anywhere
        // This isn't the right way however to set it up :(
        // Container::boot(new \Pimple\Container());
        // Container::instance()->set('occipital.api');

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
