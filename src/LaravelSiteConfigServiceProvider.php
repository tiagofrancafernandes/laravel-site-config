<?php

namespace TiagoF2\LaravelSiteConfig;

// use TiagoF2\LaravelSiteConfig\ViewComponents\Alert;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
// use Spatie\LaravelPackageTools\Commands\InstallCommand;

/**
 * The application instance.
 *
 * @property \Illuminate\Contracts\Foundation\Application $app
 */
class LaravelSiteConfigServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        // https://github.com/spatie/laravel-package-tools

        $package
            ->name('laravel-site-config')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            // ->hasViewComponents('site-config', ...[
            //     // Alert::class,
            // ])
            // ->hasViewComponent('site-config', Alert::class)
            // ->hasAssets()
            // ->publishesServiceProvider('MyProviderName')
            ->hasRoutes('web', 'api')
            ->hasMigration('create_site_configs_table')
            // ->hasCommand(YourCoolPackageCommand::class)
            // ->hasInstallCommand(function(InstallCommand $command) {
            //     $command
            //         ->publishConfigFile()
            //         ->publishAssets()
            //         ->publishMigrations()
            //         ->copyAndRegisterServiceProviderInApp()
            //         ->askToStarRepoOnGitHub();
            // })
            ->runsMigrations();
    }
}
