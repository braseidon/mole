<?php namespace Braseidon\ShutterScraper;

use Illuminate\Support\ServiceProvider;

class ShutterScraperServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Braseidon\\ShutterScraper', function()
        {
        	return
        })
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['commander'];
    }
}