<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\FilamentServiceProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;

class FilamentPtbrFormFieldsServiceProvider extends FilamentServiceProvider
{
    public function packageBooted(): void
    {
        parent::packageBooted();

        FilamentAsset::register([
            Js::make('money-script', __DIR__.'/../resources/js/money.js'),
        ]);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-ptbr-form-fields')
            ->hasConfigFile()
            ->hasViews();
    }
}
