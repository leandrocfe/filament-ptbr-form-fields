<?php

use Leandrocfe\FilamentPtbrFormFields\Money;

function parseCents(Money $field, mixed $state): mixed
{
    return (new ReflectionMethod($field, 'parseToCents'))->invoke($field, $state);
}

it('reads an integer string as cents and an integer as units', function () {
    $field = Money::make('price')->decimalInput();

    expect(parseCents($field, '100020'))->toBe(100020)
        ->and(parseCents($field, 1000))->toBe(100000);
});

it('does not mistake a pt-br thousands separator for a decimal point', function () {
    $field = Money::make('price')->decimalInput();

    expect(invokeMoney($field, 'hydrateCurrency', '1.000'))->toBe('1.000,00')
        ->and(invokeMoney($field, 'hydrateCurrency', '1000.20'))->toBe('1.000,20');
});

it('respects the position of the decimal separator', function () {
    $field = Money::make('price')->decimalInput();

    expect(parseCents($field, '10,5'))->toBe(1050)
        ->and(parseCents($field, '10,50'))->toBe(1050)
        ->and(parseCents($field, '10,999'))->toBeNull()
        ->and(parseCents($field, '1.234,5'))->toBe(123450);
});

it('keeps negative amounts negative in every branch', function () {
    $field = Money::make('price')->decimalInput();

    expect(parseCents($field, '-1.000,20'))->toBe(-100020)
        ->and(parseCents($field, -1000.20))->toBe(-100020)
        ->and(parseCents($field, '-1000.20'))->toBe(-100020)
        ->and(parseCents($field, '-100020'))->toBe(-100020)
        ->and(invokeMoney($field, 'hydrateCurrency', -1000.20))->toBe('-1.000,20')
        ->and(invokeMoney($field, 'dehydrateCurrency', '-1.000,20'))->toBe(-1000.2);
});

it('treats a whole float as cents when intFormat is enabled', function () {
    $field = Money::make('price')->decimalInput()->intFormat();

    expect(parseCents($field, 100020.0))->toBe(100020)
        ->and(parseCents($field, 100020))->toBe(100020);
});

it('returns null instead of an invented number when the amount overflows', function () {
    $field = Money::make('price')->decimalInput();

    expect(parseCents($field, 1e20))->toBeNull()
        ->and(parseCents($field, INF))->toBeNull()
        ->and(parseCents($field, '99999999999999999999'))->toBeNull();

    expect(fn () => invokeMoney($field, 'dehydrateCurrency', 1e20))->toThrow(InvalidArgumentException::class);
});

it('lets the keydown handler toggle the sign', function () {
    $attributes = Money::make('price')->getExtraAlpineAttributes();

    expect($attributes['x-on:keydown'])->toContain('FilamentPtbrFormFields.handleKeydown')
        ->and($attributes['x-on:keydown'])->toContain('$el, $event');
});

it('inlines money.js only while the asset has not been published', function () {
    $script = trim((string) file_get_contents(__DIR__.'/../resources/js/money.js'));

    $unpublished = Money::make('price')->maskAssetPublished(false)->getExtraAlpineAttributes();
    $published = Money::make('price')->maskAssetPublished(true)->getExtraAlpineAttributes();

    expect($unpublished['x-init'])->toContain($script)
        ->and($published['x-init'])->not->toContain('function maskMoney')
        ->and($published['x-init'])->toStartWith('if ($el.value');
});

it('returns null at the exact integer overflow boundary', function () {
    $field = Money::make('price')->decimalInput();

    expect(parseCents($field, 92233720368547760))->toBeNull()
        ->and(parseCents($field, '92233720368547758.07'))->toBe(PHP_INT_MAX)
        ->and(parseCents($field, 9.2233720368547758e16))->toBeNull()
        ->and(parseCents($field, 1000.20))->toBe(100020);
});

it('joins the inlined script and the init call with a semicolon', function () {
    $attributes = Money::make('price')->maskAssetPublished(false)->getExtraAlpineAttributes();

    expect($attributes['x-init'])->toContain("; if (\$el.value !== '')");
});
