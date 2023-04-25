<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PtbrCep extends TextInput
{
    /**
     * Via CEP integration
     *
     * @param  string  $mode
     * @param  string  $errorMessage
     * @param  array  $setFields
     */
    public function viaCep($mode = 'suffix', $errorMessage = 'CEP inválido.', $setFields = []): static
    {
        /**
         * @param  string  $state
         * @param  Livewire  $livewire
         * @param  Closure  $set
         * @param  TextInput  $component
         * @param  string  $errorMessage
         * @param  array  $setFields
         */
        $viaCepRequest = function ($state, $livewire, $set, $component, $errorMessage, $setFields) {
            $name = $livewire->form->getStatePath() ? $livewire->form->getStatePath() . '.' . $component->getName() : $component->getName();
            $livewire->validateOnly($name);

            /**
             * viacep api
             */
            $request = Http::get("viacep.com.br/ws/{$state}/json/")->json();

            foreach ($setFields as $key => $value) {
                $set($key, $request[$value] ?? null);
            }

            if (Arr::has($request, 'erro')) {
                throw ValidationException::withMessages([
                    $name => $errorMessage,
                ]);
            }
        };

        $this
            ->minLength(9)
            ->extraAlpineAttributes(['x-mask' => '99999-999'])
            ->afterStateUpdated(function ($state, $livewire, $set, $component) use ($errorMessage, $setFields, $viaCepRequest) {
                $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $setFields);
            })
            ->suffixAction(function ($state, $livewire, $set, $component) use ($mode, $errorMessage, $setFields, $viaCepRequest) {
                if ($mode === 'suffix') {
                    return Action::make('search-action')
                        ->label('Buscar CEP')
                        ->icon('heroicon-o-search')
                        ->action(function () use ($state, $livewire, $set, $component, $errorMessage, $setFields, $viaCepRequest) {
                            $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $setFields);
                        });
                }
            })
            ->prefixAction(function ($state, $livewire, $set, $component) use ($mode, $errorMessage, $setFields, $viaCepRequest) {
                if ($mode === 'prefix') {
                    return Action::make('search-action')
                        ->label('Buscar CEP')
                        ->icon('heroicon-o-search')
                        ->action(function () use ($state, $livewire, $set, $component, $errorMessage, $setFields, $viaCepRequest) {
                            $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $setFields);
                        });
                }
            });

        return $this;
    }
}
