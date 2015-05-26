<?php namespace Plugins\forms\Kris\LaravelFormBuilder;

use \Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {


        $this->registerFormHelper();

        $this->app->bindShared('laravel-form-builder', function ($app) {

            return new FormBuilder($app, $app['laravel-form-helper']);
        });
    }

    protected function registerFormHelper()
    {

        $this->app->bindShared('laravel-form-helper', function ($app) {

            $configuration = forms_get_config();
            $configuration['defaults'] = forms_get_config_default();

            return new FormHelper($app['view'], $app['request'], $configuration);
        });


        $this->app->alias('laravel-form-helper', '\Plugins\forms\Kris\LaravelFormBuilder\FormHelper');
    }

    public function boot()
    {

    }

    /**
     * @return string[]
     */
    public function provides()
    {
        return ['laravel-form-builder'];
    }
}
