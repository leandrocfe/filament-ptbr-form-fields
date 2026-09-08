<?php

namespace Leandrocfe\FilamentPtbrFormFields\Providers;

use Illuminate\Support\Collection;

interface DocumentProviderInterface
{
    public function fetch(string $document): null|Collection|array;
}
