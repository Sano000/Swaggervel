<?php namespace Jlapp\Swaggervel;

use Illuminate\Support\ServiceProvider;
use Jlapp\Swaggervel\Installer;

use File;
use Config;

class SwaggervelServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands(array('Jlapp\Swaggervel\InstallerCommand'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'swaggervel');

        $configFiles = File::glob(base_path("config/swagger/*.php"));
        $self = $this;
        foreach ($configFiles as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            require __DIR__ .'/routes.php';
        }
    }
}
