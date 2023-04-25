<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;

class PtbrPhone extends TextInput
{
    protected function setUp(): void
    {
        $this->dynamic();
    }

    public function dynamic(bool $condition = true): static
    {
        if ($condition) {
            $this->extraAlpineAttributes([
                'x-mask:dynamic' => '$input.length >=14 ? \'(99)99999-9999\' : \'(99)9999-9999\'',
            ])->minLength(13);
        }

        return $this;
    }

    public function format(string|Closure $format = '(99)99999-9999'): static
    {
        $this->dynamic(false)
            ->minLength(0)
            ->extraAlpineAttributes([
                'x-mask' => $format,
            ]);

        return $this;
    }
}
