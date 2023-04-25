<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\FilamentServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FilamentPtbrFormFieldsServiceProvider extends FilamentServiceProvider
{
    protected array $beforeCoreScripts = [
        'filament-apex-charts-scripts' => __DIR__.'/../dist/mask.min.js',
    ];

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
