<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;
use InvalidArgumentException;
use Leandrocfe\FilamentPtbrFormFields\Concerns\HasDocumentAutofill;
use Leandrocfe\FilamentPtbrFormFields\Providers\DocumentProviderInterface;

class Document extends TextInput
{
    use HasDocumentAutofill;

    public bool $validation = true;

    protected bool $dehydrateMask = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function (?string $state) {
            if (! $this->dehydrateMask || $state === null) {
                return $state;
            }

            return preg_replace('/[^A-Za-z0-9]/', '', $state);
        });
    }

    public function dehydrateMask(bool $condition = true): static
    {
        $this->dehydrateMask = $condition;

        return $this;
    }

    public function dynamic(bool $condition = true): static
    {
        if (self::getValidation()) {
            $this->rule('cpf_ou_cnpj');
        }

        if ($condition) {
            $this->mask(RawJs::make(<<<'JS'
                /[A-Za-z]/.test($input) || $input.replace(/[^A-Za-z0-9]/g, '').length > 11 ? '**.***.***/****-99' : '999.999.999-99'
            JS
            ))->minLength(14);
        }

        return $this;
    }

    public function cpf(string|Closure $format = '999.999.999-99'): static
    {
        $this->dynamic(false)
            ->mask($format);

        if (self::getValidation()) {
            $this->rule('cpf');
        }

        return $this;
    }

    public function cnpj(string|Closure $format = '**.***.***/****-99'): static
    {
        $this->dynamic(false)
            ->mask($format);

        if (self::getValidation()) {
            $this->rule('cnpj');
        }

        return $this;
    }

    /**
     * Configure the document provider and callback that fills the form.
     */
    public function autofill(string|DocumentProviderInterface $provider, callable $callback): static
    {
        $providerInstance = $this->resolveDocumentProvider($provider);

        $this->configureAutofill($providerInstance, $callback);

        return $this;
    }

    private function resolveDocumentProvider(string|DocumentProviderInterface $provider): DocumentProviderInterface
    {
        if (is_string($provider)) {
            if (! class_exists($provider)) {
                throw new InvalidArgumentException(
                    "The provider class [{$provider}] does not exist."
                );
            }

            $provider = new $provider;
        }

        if (! $provider instanceof DocumentProviderInterface) {
            throw new InvalidArgumentException(
                'The provider must implement the DocumentProviderInterface interface.'
            );
        }

        return $provider;
    }

    public function validation(bool|Closure $condition = true): static
    {
        $this->validation = $condition;

        return $this;
    }

    public function getValidation(): bool
    {
        return $this->validation;
    }
}
