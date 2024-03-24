<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Component as Livewire;

class Cep extends TextInput
{
    public function viaCep(string $mode = 'suffix', string $errorMessage = 'CEP inválido.', array $setFields = []): static
    {
        $viaCepRequest = function ($state, $livewire, $set, $component, $errorMessage, array $setFields) {

            $livewire->validateOnly($component->getKey());

            $request = Http::get(config('filament-ptbr-form-fields.viacep_url').$state.'/json/')->json();

            foreach ($setFields as $key => $value) {
                $set($key, $request[$value] ?? null);
            }

            if (blank($request) || Arr::has($request, 'erro')) {
                throw ValidationException::withMessages([
                    $component->getKey() => $errorMessage,
                ]);
            }
        };

        $this
            ->minLength(9)
            ->mask('99999-999')
            ->afterStateUpdated(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $setFields, $viaCepRequest) {
                $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $setFields);
            })
            ->suffixAction(function () use ($mode, $errorMessage, $setFields, $viaCepRequest) {
                if ($mode === 'suffix') {
                    return Action::make('search-action')
                        ->label('Buscar CEP')
                        ->icon('heroicon-o-magnifying-glass')
                        ->action(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $setFields, $viaCepRequest) {
                            $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $setFields);
                        })
                        ->cancelParentActions();
                }
            })
            ->prefixAction(function () use ($mode, $errorMessage, $setFields, $viaCepRequest) {
                if ($mode === 'prefix') {
                    return Action::make('search-action')
                        ->label('Buscar CEP')
                        ->icon('heroicon-o-magnifying-glass')
                        ->action(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $setFields, $viaCepRequest) {
                            $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $setFields);
                        })
                        ->cancelParentActions();
                }
            });

        return $this;
    }
}
