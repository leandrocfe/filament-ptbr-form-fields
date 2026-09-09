<?php

use Filament\Support\RawJs;
use Illuminate\Support\Facades\Validator;
use Leandrocfe\FilamentPtbrFormFields\Document;

it('applies the alphanumeric cnpj mask by default', function () {
    $field = Document::make('cnpj')->cnpj();

    expect((string) $field->getMask())->toBe('**.***.***/****-99');
});

it('accepts a custom cnpj mask format', function () {
    $field = Document::make('cnpj')->cnpj('**999999/9999-99');

    expect((string) $field->getMask())->toBe('**999999/9999-99');
});

it('applies the dynamic alphanumeric mask for cpf or cnpj', function () {
    $field = Document::make('cpf_or_cnpj')->dynamic();

    $mask = $field->getMask();

    expect($mask)->toBeInstanceOf(RawJs::class)
        ->and(trim((string) $mask))->toBe(
            "/[A-Za-z]/.test(\$input) || \$input.replace(/[^A-Za-z0-9]/g, '').length > 11 ? '**.***.***/****-99' : '999.999.999-99'"
        )
        ->and($field->getMinLength())->toBe(14);
});

it('registers the cnpj validation rule', function () {
    $field = Document::make('cnpj')->cnpj();

    expect($field->getValidationRules())->toContain('cnpj');
});

it('validates a real alphanumeric cnpj through the registered rule', function () {
    // Official example from Receita Federal's alphanumeric CNPJ specification.
    $validator = Validator::make(
        ['document' => '12ABC34501DE35'],
        ['document' => 'cnpj']
    );

    expect($validator->passes())->toBeTrue();
});

it('fails validation for an alphanumeric cnpj with a wrong check digit', function () {
    $validator = Validator::make(
        ['document' => '12ABC34501DE36'],
        ['document' => 'cnpj']
    );

    expect($validator->passes())->toBeFalse();
});
