<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use ArchTech\Money\Currency;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\Facades\FilamentAsset;
use InvalidArgumentException;
use Leandrocfe\FilamentPtbrFormFields\Currencies\BRL;

class Money extends TextInput
{
    protected string|int|float|null $initialValue = '0,00';

    protected ?Currency $currency = null;

    protected bool|Closure $dehydrateMask = false;

    protected bool|Closure $intFormat = false;

    protected ?bool $isMaskAssetPublished = null;

    protected bool|Closure $decimalInput = false;

    protected bool|Closure $emptyAsNull = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->currency()
            ->prefix('R$')
            ->inputMode('decimal')
            ->autocomplete('off')
            ->extraAlpineAttributes(fn (): array => $this->getMoneyAlpineAttributes())
            ->formatStateUsing(fn ($state) => $this->hydrateCurrency($state))
            ->dehydrateStateUsing(fn ($state) => $this->dehydrateCurrency($state));
    }

    public function initialValue(null|string|int|float|Closure $value = '0,00'): static
    {
        $this->initialValue = $value;

        return $this;
    }

    public function currency(string|null|Closure $currency = BRL::class): static
    {
        $currency = $this->evaluate($currency) ?? BRL::class;

        $this->currency = new $currency;
        currencies()->add($currency);

        if ($this->currency->code() !== 'BRL') {
            $this->prefix(null);
        }

        return $this;
    }

    public function dehydrateMask(bool|Closure $condition = true): static
    {
        $this->dehydrateMask = $condition;

        return $this;
    }

    public function intFormat(bool|Closure $intFormat = true): static
    {
        $this->intFormat = $intFormat;

        return $this;
    }

    /** Opt in to decimal unit parsing instead of the 5.0.x digit-as-cents contract. */
    public function decimalInput(bool|Closure $condition = true): static
    {
        $this->decimalInput = $condition;

        return $this;
    }

    /** Preserve an empty field as null instead of the legacy zero. */
    public function emptyAsNull(bool|Closure $condition = true): static
    {
        $this->emptyAsNull = $condition;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function getDehydrateMask(): bool
    {
        return (bool) $this->evaluate($this->dehydrateMask);
    }

    public function getIntFormat(): bool
    {
        return (bool) $this->evaluate($this->intFormat);
    }

    /**
     * @return array<string, string>
     */
    protected function getMoneyAlpineAttributes(): array
    {
        $apply = $this->getApplyMaskJavaScript();
        $bootstrap = $this->getMaskBootstrapJavaScript();
        $initialize = 'if ($el.value !== \'\') { '.$apply.' }';

        return [
            'x-init' => $bootstrap === '' ? $initialize : $bootstrap.'; '.$initialize,
            ...$this->getOnKeyPress(),
            ...$this->getOnKeyUp(),
            ...$this->getOnBlur(),
        ];
    }

    /** Legacy extension point; now supplies the keydown handler. */
    protected function getOnKeyPress(): array
    {
        return ['x-on:keydown' => 'window.FilamentPtbrFormFields.handleKeydown($el, $event, '.$this->getMaskOptionsJavaScript().')'];
    }

    /** Legacy extension point; now supplies the input capture handler. */
    protected function getOnKeyUp(): array
    {
        return ['x-on:input.capture' => $this->getApplyMaskJavaScript()];
    }

    protected function getOnBlur(): array
    {
        return ['x-on:blur' => $this->getApplyMaskJavaScript()];
    }

    protected function getApplyMaskJavaScript(): string
    {
        return 'window.FilamentPtbrFormFields.applyMask($el, '.$this->getMaskOptionsJavaScript().')';
    }

    protected function getMaskOptionsJavaScript(): string
    {
        $locale = $this->getCurrencyLocale();
        $fractionDigits = $this->getCurrency()?->displayDecimals() ?? 2;

        $empty = $this->evaluate($this->emptyAsNull) ? 'true' : 'false';

        return '{locale: \''.$locale.'\', fractionDigits: '.$fractionDigits.', empty: '.$empty.'}';
    }

    /**
     * Inline copy of resources/js/money.js, injected only when the asset has
     * not been published with `php artisan filament:assets`. Reading the file
     * keeps a single implementation of the mask in the browser.
     */
    protected function getMaskBootstrapJavaScript(): string
    {
        static $script = null;

        if ($this->isMaskAssetPublished()) {
            return '';
        }

        if ($script === null) {
            $contents = file_get_contents(__DIR__.'/../resources/js/money.js');

            if ($contents === false) {
                return '';
            }

            $script = $contents;
        }

        return $script;
    }

    /**
     * @internal Exposed so the published/unpublished branches can be tested.
     */
    public function maskAssetPublished(?bool $condition): static
    {
        $this->isMaskAssetPublished = $condition;

        return $this;
    }

    protected function isMaskAssetPublished(): bool
    {
        if ($this->isMaskAssetPublished !== null) {
            return $this->isMaskAssetPublished;
        }

        foreach (FilamentAsset::getScripts(['leandrocfe/filament-ptbr-form-fields']) as $script) {
            if ($script->getId() === 'money') {
                return $this->isMaskAssetPublished = file_exists($script->getPublicPath());
            }
        }

        return $this->isMaskAssetPublished = false;
    }

    protected function hydrateCurrency(mixed $state): string
    {
        $cents = $this->resolveCents($state);

        return $cents === null ? '' : $this->formatCents($cents, prefix: false);
    }

    protected function dehydrateCurrency(mixed $state): int|float|string|null
    {
        $cents = $this->resolveCents($state);

        if ($cents === null) {
            return null;
        }

        if ($this->getDehydrateMask()) {
            return $this->formatCents($cents);
        }

        if ($this->getIntFormat()) {
            return $cents;
        }

        // Preserve the legacy float return for ordinary amounts. Large amounts
        // need a decimal string, since a float cannot retain their exact cents.
        $decimal = $this->decimalFromCents($cents);

        // Keep a half-cent rounding margin after scaling to decimal units.
        // JavaScript's safe integer limit alone is insufficient here.
        return $cents > 4503599627370496 || $cents < -4503599627370496 ? $decimal : (float) $decimal;
    }

    protected function resolveCents(mixed $state): ?int
    {
        if (is_float($state) && (! is_finite($state) || abs($state) > 9007199254740991)) {
            throw new InvalidArgumentException('Money floats must be finite and within the safe integer range; use exact strings or integer cents.');
        }

        if (($state === null || $state === '') && $this->evaluate($this->emptyAsNull)) {
            return null;
        }

        $cents = $this->evaluate($this->decimalInput)
            ? $this->parseToCents($state)
            : $this->sanitizeState($state === null ? null : (string) $state);

        if ($cents === null) {
            throw new InvalidArgumentException('Money input is ambiguous, invalid, or outside the supported integer range.');
        }

        return $cents;
    }

    /**
     * Retains the 5.0.1 extension point and digit-as-cents input semantics.
     * Integer conversion is checked before casting to avoid overflow saturation.
     */
    protected function sanitizeState(?string $state): ?int
    {
        $raw = str_replace(['.', ','], '', $state ?? '');

        // PHP's legacy integer conversion accepts scientific notation. Keep
        // that behavior only where the exponent can be represented safely.
        if (is_numeric($raw) && stripos($raw, 'e') !== false) {
            $number = (float) $raw;

            return is_finite($number) && abs($number) <= 9007199254740991 ? (int) $number : null;
        }

        preg_match('/^\s*([+-]?\d+)/', $raw, $matches);

        return $this->integerCents($matches[1] ?? '0');
    }

    protected function parseToCents(mixed $state): ?int
    {
        if ($state === null || $state === '') {
            return 0;
        }

        $decimals = $this->getCurrency()?->mathDecimals() ?? 2;

        if ($this->getIntFormat() && $this->isIntegerLike($state)) {
            return $this->integerCents((string) $state);
        }

        if (is_int($state)) {
            return $this->integerCents((string) $state.str_repeat('0', $decimals));
        }

        if (is_float($state)) {
            if (! is_finite($state) || abs($state) * (10 ** $decimals) > 4503599627370496) {
                return null;
            }

            return $this->integerCents(sprintf('%.0f', round($state * (10 ** $decimals))));
        }

        $raw = trim((string) $state);
        $decimalSeparator = $this->getCurrency()?->decimalSeparator() ?? ',';
        $thousandsSeparator = $this->getCurrency()?->thousandsSeparator() ?? '.';

        if (preg_match('/^-?\d+$/', $raw) === 1) {
            return $this->integerCents($raw);
        }

        $grouped = $thousandsSeparator === '.' && $decimalSeparator !== '.'
            && preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $raw) === 1;

        if ($grouped && substr_count($raw, '.') === 1 && ! str_ends_with($raw, '.000')) {
            return null;
        }

        if ($grouped) {
            return $this->integerCents(str_replace('.', '', $raw).str_repeat('0', $decimals));
        }

        if (preg_match('/^-?\d+\.\d+$/', $raw) === 1) {
            return $this->decimalToCents($raw, '.', $decimals);
        }

        $localized = str_replace($thousandsSeparator, '', $raw);

        return $this->decimalToCents($localized, $decimalSeparator, $decimals);
    }

    protected function decimalToCents(string $raw, string $separator, int $decimals): ?int
    {
        if (preg_match('/^-?\d+(?:'.preg_quote($separator, '/').'\d+)?$/', $raw) !== 1) {
            return null;
        }

        [$integer, $fraction] = array_pad(explode($separator, $raw, 2), 2, '');

        if (trim(substr($fraction, $decimals), '0') !== '') {
            return null;
        }

        return $this->integerCents($integer.substr(str_pad($fraction, $decimals, '0'), 0, $decimals));
    }

    /** Convert decimal digit strings to PHP integers without passing through float. */
    protected function integerCents(string $digits): ?int
    {
        if (preg_match('/^[+-]?\d+$/', $digits) !== 1) {
            return null;
        }

        $negative = str_starts_with($digits, '-');
        $absolute = ltrim(ltrim($digits, '+-'), '0') ?: '0';
        $limit = $negative ? substr((string) PHP_INT_MIN, 1) : (string) PHP_INT_MAX;

        if (strlen($absolute) > strlen($limit) || (strlen($absolute) === strlen($limit) && strcmp($absolute, $limit) > 0)) {
            return null;
        }

        return (int) (($negative ? '-' : '').$absolute);
    }

    protected function decimalFromCents(int $cents): string
    {
        $decimals = $this->getCurrency()?->mathDecimals() ?? 2;
        $digits = str_pad(ltrim((string) $cents, '-'), $decimals + 1, '0', STR_PAD_LEFT);
        $decimal = $decimals === 0 ? $digits : substr($digits, 0, -$decimals).'.'.substr($digits, -$decimals);

        return ($cents < 0 ? '-' : '').$decimal;
    }

    /** Format without archtechx/money's intermediate float conversion. */
    protected function formatCents(int $cents, bool $prefix = true): string
    {
        $currency = $this->getCurrency();
        $math = $currency?->mathDecimals() ?? 2;
        $display = $currency?->displayDecimals() ?? 2;
        $digits = ltrim((string) $cents, '-');

        if ($display < $math) {
            $digits = str_pad($digits, $math - $display + 1, '0', STR_PAD_LEFT);
            $roundUp = (int) $digits[strlen($digits) - ($math - $display)] >= 5;
            $digits = substr($digits, 0, -($math - $display));

            if ($roundUp) {
                // Carry on strings, including values close to PHP_INT_MAX.
                $digits = '0'.$digits;
                for ($i = strlen($digits) - 1; $i >= 0; $i--) {
                    if ($digits[$i] !== '9') {
                        $digits[$i] = (string) ((int) $digits[$i] + 1);
                        break;
                    }
                    $digits[$i] = '0';
                }
                $digits = ltrim($digits, '0') ?: '0';
            }
        } else {
            $digits .= str_repeat('0', $display - $math);
        }

        $digits = str_pad($digits, $display + 1, '0', STR_PAD_LEFT);
        $integer = $display === 0 ? $digits : substr($digits, 0, -$display);
        $fraction = $display === 0 ? '' : substr($digits, -$display);
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', $currency?->thousandsSeparator() ?? '.', $integer);

        if ($currency?->trimTrailingDecimalZeros()) {
            $fraction = rtrim($fraction, '0');
        }

        $formatted = ($cents < 0 && trim($digits, '0') !== '' ? '-' : '').$integer
            .($fraction === '' ? '' : ($currency?->decimalSeparator() ?? ',').$fraction);

        return ($prefix ? ($currency?->prefix() ?? '') : '').$formatted.($currency?->suffix() ?? '');
    }

    protected function isIntegerLike(mixed $state): bool
    {
        return is_int($state)
            || (is_float($state) && is_finite($state) && abs($state) <= 9007199254740991 && floor($state) === $state)
            || (is_string($state) && preg_match('/^-?\d+$/', trim($state)) === 1);
    }

    protected function getCurrencyLocale(): string
    {
        $currency = $this->getCurrency();

        if ($currency !== null && isset($currency->locale) && is_string($currency->locale) && $currency->locale !== '') {
            return $currency->locale;
        }

        return 'pt-BR';
    }
}
