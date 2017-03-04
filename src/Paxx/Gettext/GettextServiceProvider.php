<?php

namespace Paxx\Gettext;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class GettextServiceProvider extends ServiceProvider {
    /** @var Gettext */
	protected $instance = null;

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{	
		$this->instance = new Gettext();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return;
        }

        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');
        $encoding   = $config->get('gettext.encoding');
        $textDomain = $config->get('gettext.textdomain');
        $path       = $config->get('gettext.path');
        $target     = $config->get('gettext.target');

        $this->instance->setTarget($target);
        $this->instance->setTextdomain($textDomain, $path);
        $this->instance->setEncoding($encoding);
        $this->instance->setLocale($this->app->getLocale());

        // Listen for locale changes and propagate them to gettext.
        /** @var \Illuminate\Events\Dispatcher $events */
        $events = $this->app->make('events');

        // Laravel 5.0-5.3
        $events->listen('locale.changed', function($locale) {
            $this->instance->setLocale($locale);
        });

        // Laravel 5.4
        $events->listen(\Illuminate\Foundation\Events\LocaleUpdated::class, function($event) {
            /** @var \Illuminate\Foundation\Events\LocaleUpdated $event */
            $this->instance->setLocale($event->locale);
        });
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		config(array(
			'config/config.php'
		));

        $this->app->singleton('gettext', $this->instance);

		$this->app->booting(function() {
		    $loader = AliasLoader::getInstance();
		    $loader->alias('Gettext', 'Paxx\Gettext\Facades\Gettext');
		});

		$this->registerExtractCommand();
	}


	public function registerExtractCommand ()
	{
        $this->app->singleton('gettext.gettext', function($app) {
            return new Commands\GettextCommand();
        });

	    $this->commands('gettext.gettext');
	}



	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('Gettext');
	}

}
