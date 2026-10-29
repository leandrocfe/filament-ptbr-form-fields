<?php

use Filament\Support\Facades\FilamentAsset;
use Leandrocfe\FilamentPtbrFormFields\Currencies\BRL;
use Leandrocfe\FilamentPtbrFormFields\Currencies\USD;
use Leandrocfe\FilamentPtbrFormFields\Money;

it('hydrates a formatted brl default as a masked value', function () {
    $field = Money::make('price');

    expect(invokeMoney($field, 'hydrateCurrency', '100,00'))->toBe('100,00');
});

it('hydrates a database decimal float using two fraction digits', function () {
    $field = Money::make('price')->decimalInput();

    expect(invokeMoney($field, 'hydrateCurrency', 100.0))->toBe('100,00')
        ->and(invokeMoney($field, 'hydrateCurrency', 1000.2))->toBe('1.000,20');
});

it('hydrates a canonical decimal string from the database', function () {
    $field = Money::make('price')->decimalInput();

    expect(invokeMoney($field, 'hydrateCurrency', '1000.20'))->toBe('1.000,20');
});

it('hydrates integer cents when intFormat is enabled', function () {
    $field = Money::make('price')->intFormat();

    expect(invokeMoney($field, 'hydrateCurrency', 100020))->toBe('1.000,20')
        ->and(invokeMoney($field, 'hydrateCurrency', '100020'))->toBe('1.000,20');
});

it('dehydrates a masked value to a decimal by default', function () {
    $field = Money::make('price');

    expect(invokeMoney($field, 'dehydrateCurrency', '1.000,20'))->toBe(1000.2)
        ->and(invokeMoney($field, 'dehydrateCurrency', '100020'))->toBe(1000.2);
});

it('dehydrates a masked value to integer cents when intFormat is enabled', function () {
    $field = Money::make('price')->intFormat();

    expect(invokeMoney($field, 'dehydrateCurrency', '1.000,20'))->toBe(100020);
});

it('dehydrates a masked value keeping the formatted mask', function () {
    $field = Money::make('price')->dehydrateMask();

    expect(invokeMoney($field, 'dehydrateCurrency', '1.000,20'))->toBe('1.000,20');
});

it('opts in to dehydrating an empty value as null', function () {
    $field = Money::make('price')->emptyAsNull();

    expect(invokeMoney($field, 'dehydrateCurrency', null))->toBeNull()
        ->and(invokeMoney($field, 'dehydrateCurrency', ''))->toBeNull();
});

it('hydrates an empty value as an empty string', function () {
    $field = Money::make('price')->emptyAsNull();

    expect(invokeMoney($field, 'hydrateCurrency', null))->toBe('')
        ->and(invokeMoney($field, 'hydrateCurrency', ''))->toBe('');
});

it('evaluates dehydrateMask closures', function () {
    $field = Money::make('price')->dehydrateMask(fn (): bool => false);

    expect(invokeMoney($field, 'dehydrateCurrency', '100,00'))->toBe(100.0);
});

it('keeps the brl prefix by default and clears it for other currencies', function () {
    expect(Money::make('price')->getPrefixLabel())->toBe('R$');

    $usd = Money::make('price')->currency(USD::class);

    expect($usd->getPrefixLabel())->toBeNull()
        ->and($usd->getCurrency()?->code())->toBe('USD');
});

it('does not clear the brl prefix when currency is set to the brl class', function () {
    $field = Money::make('price')->currency(BRL::class);

    expect($field->getPrefixLabel())->toBe('R$');
});

it('registers alpine handlers that format on input instead of blocking separators only', function () {
    $field = Money::make('price');
    $attributes = $field->getExtraAlpineAttributes();

    expect($attributes)->toHaveKey('x-on:input.capture')
        ->and($attributes)->toHaveKey('x-init')
        ->and($attributes)->toHaveKey('x-on:blur')
        ->and($attributes)->not->toHaveKey('x-on:keypress')
        ->and($attributes['x-on:input.capture'])->toContain('applyMask')
        ->and($attributes['x-init'])->toContain('FilamentPtbrFormFields')
        ->and($attributes['x-init'])->toContain('maskMoney')
        ->and($attributes['x-init'])->not->toContain('Currency.masking');
});

it('registers the money script under the composer package name', function () {
    $ids = array_map(
        fn ($script): string => $script->getId(),
        FilamentAsset::getScripts(['leandrocfe/filament-ptbr-form-fields']),
    );

    expect($ids)->toContain('money');
});

it('preserves the 5.0.1 default input and empty contract', function (mixed $input, string $display, float $decimal) {
    $field = Money::make('price');

    expect(invokeMoney($field, 'hydrateCurrency', $input))->toBe($display)
        ->and(invokeMoney($field, 'dehydrateCurrency', $input))->toBe($decimal);
})->with([
    ['1.230', '12,30', 12.3],
    ['1.000', '10,00', 10.0],
    ['10,5', '1,05', 1.05],
    [1000, '10,00', 10.0],
    [100.0, '1,00', 1.0],
    [1000.2, '100,02', 100.02],
    [null, '0,00', 0.0],
    ['', '0,00', 0.0],
]);

it('keeps empty integer and masked outputs compatible by default', function () {
    expect(invokeMoney(Money::make('price')->intFormat(), 'dehydrateCurrency', ''))->toBe(0)
        ->and(invokeMoney(Money::make('price')->dehydrateMask(), 'dehydrateCurrency', null))->toBe('0,00');
});

it('evaluates opt-in closures and can restore legacy behavior', function () {
    $field = Money::make('price')->decimalInput(fn () => false)->emptyAsNull(fn () => false);

    expect(invokeMoney($field, 'dehydrateCurrency', '1.230'))->toBe(12.3)
        ->and(invokeMoney($field, 'dehydrateCurrency', ''))->toBe(0.0);
});

it('reports ambiguous strict input explicitly rather than erasing it', function () {
    $field = Money::make('price')->decimalInput();

    expect(fn () => invokeMoney($field, 'hydrateCurrency', '1.230'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => invokeMoney($field, 'dehydrateCurrency', '1000.2001'))->toThrow(InvalidArgumentException::class)
        ->and(invokeMoney($field, 'hydrateCurrency', '1000.2000'))->toBe('1.000,20');
});

it('retains exact cents above the JavaScript safe integer range', function (int|string $cents, string $display) {
    $field = Money::make('price')->intFormat();

    expect(invokeMoney($field, 'hydrateCurrency', $cents))->toBe($display)
        ->and(invokeMoney($field, 'dehydrateCurrency', $display))->toBe((int) $cents);
})->with([
    [9007199254740993, '90.071.992.547.409,93'],
    ['9007199254740993', '90.071.992.547.409,93'],
    [PHP_INT_MAX, '92.233.720.368.547.758,07'],
    [PHP_INT_MIN, '-92.233.720.368.547.758,08'],
]);

it('returns exact large decimals as strings instead of rounded floats', function () {
    $field = Money::make('price')->decimalInput();

    expect(invokeMoney($field, 'hydrateCurrency', '90071992547409.93'))->toBe('90.071.992.547.409,93')
        ->and(invokeMoney($field, 'dehydrateCurrency', '90.071.992.547.409,93'))->toBe('90071992547409.93');
});

it('preserves and invokes the protected Money extension points', function () {
    $field = new class('price') extends Money
    {
        protected function sanitizeState(?string $state): ?int
        {
            return 123;
        }

        protected function getOnKeyPress(): array
        {
            return ['x-on:keypress' => 'customPress()'];
        }

        protected function getOnKeyUp(): array
        {
            return ['x-on:keyup' => 'customUp()'];
        }

        protected function getOnBlur(): array
        {
            return ['x-on:blur' => 'customBlur()'];
        }
    };
    $field->configure();

    expect(invokeMoney($field, 'hydrateCurrency', '999'))->toBe('1,23')
        ->and(invokeMoney($field, 'dehydrateCurrency', '999'))->toBe(1.23)
        ->and($field->getExtraAlpineAttributes())->toMatchArray([
            'x-on:keypress' => 'customPress()',
            'x-on:keyup' => 'customUp()',
            'x-on:blur' => 'customBlur()',
        ]);
});

it('rejects overflowing legacy inputs and unsafe floats without substituting another value', function (mixed $input) {
    $field = Money::make('price');

    expect(fn () => invokeMoney($field, 'hydrateCurrency', $input))->toThrow(InvalidArgumentException::class)
        ->and(fn () => invokeMoney($field, 'dehydrateCurrency', $input))->toThrow(InvalidArgumentException::class);
})->with([
    [1e20],
    [INF],
    [9007199254740992.0],
    ['9223372036854775808'],
    ['-9223372036854775809'],
]);

it('returns strings before decimal floats lose cent resolution', function (string $masked, string $decimal) {
    expect(invokeMoney(Money::make('price'), 'dehydrateCurrency', $masked))->toBe($decimal);
})->with([
    ['70.368.744.177.664,01', '70368744177664.01'],
    ['-70.368.744.177.664,01', '-70368744177664.01'],
    ['45.035.996.273.704,97', '45035996273704.97'],
]);

it('preserves ordinary decimal floats and the conservative boundary', function () {
    expect(invokeMoney(Money::make('price'), 'dehydrateCurrency', '123,45'))->toBe(123.45)
        ->and(invokeMoney(Money::make('price'), 'dehydrateCurrency', '45.035.996.273.704,96'))->toBe(45035996273704.96);
});

it('rejects decimal-unit floats that no longer resolve exact cents', function () {
    expect(fn () => invokeMoney(Money::make('price')->decimalInput(), 'hydrateCurrency', 70368744177664.01))
        ->toThrow(InvalidArgumentException::class);
});
