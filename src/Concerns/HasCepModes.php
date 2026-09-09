<?php

namespace Leandrocfe\FilamentPtbrFormFields\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Leandrocfe\FilamentPtbrFormFields\CepFieldMode;
use Leandrocfe\FilamentPtbrFormFields\Providers\CepProviderInterface;
use Livewire\Component;

trait HasCepModes
{
    protected CepFieldMode $mode = CepFieldMode::ON_BLUR;

    public function mode(CepFieldMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): CepFieldMode
    {
        return $this->mode;
    }

    protected function configureFieldMode(CepProviderInterface $provider, callable $callback): static
    {
        return match ($this->getMode()) {
            CepFieldMode::ON_BLUR => $this->configureOnBlurMode($provider, $callback),
            CepFieldMode::SUFFIX => $this->configureSuffixMode($provider, $callback),
            default => $this,
        };
    }

    protected function configureOnBlurMode(CepProviderInterface $provider, callable $callback): static
    {
        return $this
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set, TextInput $component, Component $livewire) use ($provider, $callback) {
                $livewire->validateOnly($component->getStatePath());

                $response = $this->fetchCepData($state, $provider);

                if (blank($response)) {
                    $livewire->addError($component->getStatePath(), $this->getErrorMessage());
                }

                $callback($set, $response);
            });
    }

    protected function configureSuffixMode(CepProviderInterface $provider, callable $callback): static
    {
        return $this
            ->suffixAction(function () use ($provider, $callback) {
                return Action::make('searchCep')
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->action(function (?string $state, Set $set, TextInput $component, Component $livewire) use ($provider, $callback) {
                        $livewire->validateOnly($component->getStatePath());

                        $response = $this->fetchCepData($state, $provider);

                        if (blank($response)) {
                            $livewire->addError($component->getStatePath(), $this->getErrorMessage());
                        }

                        $callback($set, $response);
                    })
                    ->cancelParentActions();
            });
    }

    protected function fetchCepData(?string $cep, CepProviderInterface $provider): null|array|Collection
    {
        if (blank($cep)) {
            return null;
        }

        return $provider->fetch($cep);
    }
}
