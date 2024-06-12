<?php

namespace Leandrocfe\FilamentPtbrFormFields\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Leandrocfe\FilamentPtbrFormFields\FilamentPtbrFormFieldsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Leandrocfe\\FilamentPtbrFormFields\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            FilamentPtbrFormFieldsServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_filament-ptbr-form-fields_table.php.stub';
        $migration->up();
        */
    }
}
