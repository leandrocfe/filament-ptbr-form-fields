<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Validator;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Leandrocfe\FilamentPtbrFormFields\PtbrCpfCnpj;
use Livewire\Component;

it('provides mask constants for cpf, numeric cnpj and alphanumeric cnpj', function () {
    expect(Document::CPF_MASK)->toBe('999.999.999-99')
        ->and(Document::CNPJ_MASK)->toBe('99.999.999/9999-99')
        ->and(Document::CNPJ_ALPHANUMERIC_MASK)->toBe('**.***.***/****-99');
});

it('applies the alphanumeric cnpj mask by default', function () {
    $field = Document::make('cnpj')->cnpj();

    expect((string) $field->getMask())->toBe(Document::CNPJ_ALPHANUMERIC_MASK);
});

it('applies the classic numeric cnpj mask when requested via named parameter', function () {
    $field = Document::make('cnpj')->cnpj(alphanumeric: false);

    expect((string) $field->getMask())->toBe(Document::CNPJ_MASK);
});

it('applies the classic numeric cnpj mask when passing false as first argument', function () {
    $field = Document::make('cnpj')->cnpj(false);

    expect((string) $field->getMask())->toBe(Document::CNPJ_MASK);
});

it('applies the classic numeric cnpj mask using the constant', function () {
    $field = Document::make('cnpj')->cnpj(Document::CNPJ_MASK);

    expect((string) $field->getMask())->toBe('99.999.999/9999-99');
});

it('accepts a custom cnpj mask format', function () {
    $field = Document::make('cnpj')->cnpj('**999999/9999-99');

    expect((string) $field->getMask())->toBe('**999999/9999-99');
});

it('applies the dynamic alphanumeric mask for cpf or cnpj by default', function () {
    $field = Document::make('cpf_or_cnpj')->dynamic();

    $mask = $field->getMask();

    expect($mask)->toBeInstanceOf(RawJs::class)
        ->and(trim((string) $mask))->toBe(
            "/[A-Za-z]/.test(\$input) || \$input.replace(/[^A-Za-z0-9]/g, '').length > 11 ? '**.***.***/****-99' : '999.999.999-99'"
        )
        ->and($field->getMinLength())->toBe(14);
});

it('applies the dynamic numeric mask for cpf or cnpj when alphanumeric is false', function () {
    $field = Document::make('cpf_or_cnpj')->dynamic(alphanumeric: false);

    $mask = $field->getMask();

    expect($mask)->toBeInstanceOf(RawJs::class)
        ->and(trim((string) $mask))->toBe(
            "\$input.replace(/\\D/g, '').length > 11 ? '99.999.999/9999-99' : '999.999.999-99'"
        )
        ->and($field->getMinLength())->toBe(14);
});

function dehydrate(Document $field, ?string $state): mixed
{
    return $field->mutateDehydratedState($state);
}

it('strips only mask punctuation and converts to uppercase when dehydrating an alphanumeric cnpj', function () {
    $field = Document::make('cnpj')->cnpj()->dehydrateMask();

    expect(dehydrate($field, '12.abc.345/01de-35'))->toBe('12ABC34501DE35');
});

it('converts to uppercase when dehydrating with mask preserved', function () {
    $field = Document::make('cnpj')->cnpj()->dehydrateMask(false);

    expect(dehydrate($field, '12.abc.345/01de-35'))->toBe('12.ABC.345/01DE-35');
});

it('keeps a plain numeric cnpj untouched when dehydrating', function () {
    $field = Document::make('cnpj')->cnpj()->dehydrateMask();

    expect(dehydrate($field, '11.222.333/0001-81'))->toBe('11222333000181');
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

it('validates a classic numeric cnpj through the registered rule', function () {
    $validator = Validator::make(
        ['document' => '11222333000181'],
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

it('supports classic and alphanumeric masks in deprecated PtbrCpfCnpj', function () {
    $alphanumeric = PtbrCpfCnpj::make('cnpj')->cnpj();
    expect((string) $alphanumeric->getMask())->toBe(PtbrCpfCnpj::CNPJ_ALPHANUMERIC_MASK);

    $numeric = PtbrCpfCnpj::make('cnpj')->cnpj(alphanumeric: false);
    expect((string) $numeric->getMask())->toBe(PtbrCpfCnpj::CNPJ_MASK);
});

it('applies the cpf mask and rule by default', function () {
    $field = Document::make('cpf')->cpf();

    expect((string) $field->getMask())->toBe(Document::CPF_MASK)
        ->and($field->getValidationRules())->toContain('cpf');
});

it('accepts a closure as the cnpj mask format', function () {
    $field = Document::make('cnpj')->cnpj(fn (): string => Document::CNPJ_MASK);

    expect((string) $field->getMask())->toBe(Document::CNPJ_MASK);
});

it('registers the cpf_ou_cnpj rule on dynamic fields', function () {
    $field = Document::make('cpf_or_cnpj')->dynamic();

    expect($field->getValidationRules())->toContain('cpf_ou_cnpj');
});

it('does not register document rules when validation is disabled', function () {
    $cnpj = Document::make('cnpj')->validation(false)->cnpj();
    $dynamic = Document::make('cpf_or_cnpj')->validation(false)->dynamic();

    expect($cnpj->getValidationRules())->not->toContain('cnpj')
        ->and($cnpj->getValidationRules())->not->toContain('cpf_ou_cnpj')
        ->and($dynamic->getValidationRules())->not->toContain('cpf_ou_cnpj');
});

it('keeps a null state untouched when dehydrating', function () {
    $field = Document::make('cnpj')->cnpj()->dehydrateMask();

    expect(dehydrate($field, null))->toBeNull();
});

it('uppercases the state used for validation', function () {
    $field = Document::make('cnpj')->cnpj();

    expect($field->mutateStateForValidation('12.abc.345/01de-35'))->toBe('12.ABC.345/01DE-35')
        ->and($field->mutateStateForValidation(null))->toBeNull();
});

it('keeps the uppercase style when the user sets extra input attributes', function () {
    $field = Document::make('cnpj')->cnpj()->extraInputAttributes(['data-foo' => 'bar']);

    expect($field->getExtraInputAttributes())
        ->toHaveKey('data-foo', 'bar')
        ->toHaveKey('style', 'text-transform: uppercase');
});

it('lets the user override the uppercase style explicitly', function () {
    $field = Document::make('cnpj')->cnpj()->extraInputAttributes(['style' => 'color: red']);

    expect($field->getExtraInputAttributes()['style'])->toBe('color: red');
});

it('supports the numeric dynamic mask in deprecated PtbrCpfCnpj', function () {
    $field = PtbrCpfCnpj::make('cpf_or_cnpj')->dynamic(alphanumeric: false);

    expect(trim((string) $field->getMask()))->toBe(
        "\$input.replace(/\\D/g, '').length > 11 ? '99.999.999/9999-99' : '999.999.999-99'"
    );
});

it('keeps a non string state untouched across every hook', function () {
    $field = Document::make('cnpj')->cnpj()->dehydrateMask();

    expect($field->mutateDehydratedState(['12abc34501de35']))->toBe(['12abc34501de35'])
        ->and($field->mutateStateForValidation(null))->toBeNull();
});

it('normalizes lowercase CNPJ through the real schema validation and dehydration flow', function () {
    $component = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        public array $data = [];

        public function form(Schema $schema): Schema
        {
            return $schema->statePath('data')->components([
                Document::make('cnpj')->cnpj()->required()->rules(['regex:/^[^a-z]+$/']),
            ]);
        }
    };

    $schema = $component->form;
    $schema->fill(['cnpj' => '12.ABC.345/01DE-35']);
    // Simulate new input after hydration, which does not execute formatStateUsing.
    $component->data['cnpj'] = '12.abc.345/01de-35';
    $state = $schema->getState();

    expect($state['cnpj'])->toBe('12.ABC.345/01DE-35');
});
