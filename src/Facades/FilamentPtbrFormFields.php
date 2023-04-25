<?php

namespace Leandrocfe\FilamentPtbrFormFields\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Leandrocfe\FilamentPtbrFormFields\FilamentPtbrFormFields
 */
class FilamentPtbrFormFields extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Leandrocfe\FilamentPtbrFormFields\FilamentPtbrFormFields::class;
    }
}
