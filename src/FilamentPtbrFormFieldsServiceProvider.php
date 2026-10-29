<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPtbrFormFieldsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-ptbr-form-fields')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Js::make('money', __DIR__.'/../resources/js/money.js'),
        ], 'leandrocfe/filament-ptbr-form-fields');
    }
}
