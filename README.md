# Brazilian pt-BR form fields.

This package provides custom form fields for [Filament](https://filamentphp.com/) that are commonly used in Brazilian web applications, such as CPF/CNPJ validation, phone number formatting, money with currency symbol, and CEP integration with [ViaCep](https://viacep.com.br).

This package uses [LaravelLegends/pt-br-validator](https://github.com/LaravelLegends/pt-br-validator) to validate Brazilian Portuguese fields.

![image demo](https://raw.githubusercontent.com/leandrocfe/filament-ptbr-form-fields/develop/screenshots/v3x-example.png)

## Installation

Version 5.1 targets PHP 8.2+, Filament 5, and `laravellegends/pt-br-validator` `^12.2 || ^13.0`.
The validator minimum is now 12.2 for alphanumeric CNPJ support; applications pinned to 10.x, 11.x,
or 12.0–12.1 must upgrade that dependency before upgrading this package.

You can install the package via Composer:

```bash
composer require leandrocfe/filament-ptbr-form-fields:"^5.0"
php artisan filament:assets
```

### Filament V2 - if you are using Filament v2.x, you can use [this section](https://github.com/leandrocfe/filament-ptbr-form-fields/tree/2.0.0)
### Filament V3 - if you are using Filament v3.x, you can use [this section](https://github.com/leandrocfe/filament-ptbr-form-fields/tree/3.x)
### Filament V4 - if you are using Filament v4.x, you can use [this section](https://github.com/leandrocfe/filament-ptbr-form-fields/tree/4.x)

## Usage

### CPF / CNPJ

To create a dynamic input that accepts either CPF or CNPJ, use:

```php
use Leandrocfe\FilamentPtbrFormFields\Document;
//CPF or CNPJ
Document::make('cpf_or_cnpj')
    ->dynamic()
```

If you want to create an input that only accepts CPF or only accepts CNPJ, use:

```php
//CPF
Document::make('cpf')
    ->cpf()
```

```php
// CNPJ (accepts the new alphanumeric format by default: **.***.***/****-99, also compatible with numeric CNPJs)
Document::make('cnpj')
    ->cnpj()

// Or strictly enforce the classic numeric CNPJ format: 99.999.999/9999-99
Document::make('cnpj')
    ->cnpj(alphanumeric: false)
```

For dynamic CPF/CNPJ inputs:

```php
// Dynamic CPF or CNPJ (supports alphanumeric CNPJ by default)
Document::make('cpf_or_cnpj')
    ->dynamic()

// Dynamic CPF or CNPJ using only the classic numeric masks
Document::make('cpf_or_cnpj')
    ->dynamic(alphanumeric: false)
```

> `alphanumeric: false` only restricts the **mask**. The `cpf_ou_cnpj` validation rule still accepts an
> alphanumeric CNPJ that reaches the field from another source (an imported record, a request payload, etc.).

`cnpj()` also accepts the flag positionally, which is handy when you do not need a custom mask:

```php
Document::make('cnpj')
    ->cnpj(false) // same as ->cnpj(alphanumeric: false)
```

You can also use mask constants directly:

```php
Document::CPF_MASK;               // '999.999.999-99'
Document::CNPJ_MASK;              // '99.999.999/9999-99'
Document::CNPJ_ALPHANUMERIC_MASK; // '**.***.***/****-99'

Document::make('cnpj')
    ->cnpj(Document::CNPJ_MASK)
```

If you want to use a custom mask for the input, use the `cpf()` or `cnpj()` method with a string argument representing the desired mask:

```php
Document::make('cpf')
    ->cpf('999999999-99')
```

```php
Document::make('cnpj')
    ->cnpj('99999999/9999-99')
```

### Uppercase normalization

The alphanumeric CNPJ (Anexo XV of IN RFB nº 2.119/2022) only assigns values to **uppercase** letters, so
`Document` normalizes the field:

- the input is displayed in uppercase (`text-transform: uppercase`);
- the state is uppercased before validation and before being dehydrated (saved).

> **Behavior change:** the dehydrated state is now always uppercased, even when `dehydrateMask()` is not used.
> A value typed as `12.abc.345/01de-35` is saved as `12.ABC.345/01DE-35` (or `12ABC34501DE35` with
> `dehydrateMask()`). While the user is still typing, the live Livewire state keeps the original casing, so
> a custom `afterStateUpdated()` callback may receive lowercase characters.

### Validation
`Document` uses [LaravelLegends/pt-br-validator](https://github.com/LaravelLegends/pt-br-validator) to validate Brazilian Portuguese fields by default - `cpf_ou_cnpj` | `cpf` | `cnpj`

You can disable validation using the `validation(false)` method:

```php
Document::make('cpf_or_cnpj')
    ->validation(false)
    ->dynamic()
```

```php
Document::make('cpf')
    ->validation(false)
    ->cpf()
```

### Phone number

To create a dynamic input that formats phone numbers with DDD, use:

```php
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;
PhoneNumber::make('phone_number')
```

If you want to use a custom phone number format, use the `mask() method with a string argument representing the desired format:

```php
PhoneNumber::make('phone_number')
    ->mask('(99) 99999-9999')
```

```php
PhoneNumber::make('phone_number')
    ->mask('+99 (99) 99999-9999')
```

### Money

To create a money input field, use the following syntax:

```php
use Leandrocfe\FilamentPtbrFormFields\Money;
Money::make('price')
    ->default('100,00')

#output: 100.00
```

The field uses a calculator-style mask: type only digits and the thousands/decimal separators are applied for you. Typing `100020` displays `1.000,20`. You do not type `.` or `,`.

When editing an amount in the middle, the caret and selected digit range stay at the same logical digits as the mask adds or removes separators. If Backspace or Delete lands directly on a separator, the adjacent digit is removed while preserving the caret by digit position. Pressing `-` toggles the sign while retaining the caret or selection. Both operations dispatch a bubbling `input` event, so `wire:model` receives the updated value. Readonly and disabled inputs are preserved.

#### Input compatibility and optional decimal parsing

By default, 5.1 preserves the 5.0.1 input contract: remove `.` and `,`, then read the remaining
integer as cents. For example, `1.230` displays `12,30`, `1.000` displays `10,00`, `10,5` displays
`1,05`, and an integer `1000` displays `10,00`. Empty or null input displays `0,00` and saves zero.
`intFormat()` chooses integer-cent output; it does not change this default input contract.

For persisted decimal units (including database floats), explicitly opt in:

```php
Money::make('price')
    ->decimalInput()
    ->emptyAsNull() // optional: blank input displays blank and saves null
```

In `decimalInput()` mode, numeric `1000` and `1000.2` represent units, canonical strings `1000.20`
and `1000.2000` represent 1000.2 units, and digit-only strings remain calculator-style cents.
With `intFormat()`, integer numbers and integer strings represent cents. Localized values such as
`1.234,50` are supported. Extra fractional digits must be zero: `1000.2001` is rejected at scale 2.
Ambiguous BRL strings such as `1.230` are rejected in this opt-in mode; use `1.23` for 1.23 units or
`1.230,00` for 1230 units. The explicit `.000` convention (`1.000`) and multiple groups
(`1.234.567`) represent thousands. This convention applies to currencies with `.` as their
thousands separator and a different decimal separator.

Invalid, ambiguous, or overflowing input throws `InvalidArgumentException` during hydration or
dehydration, instead of clearing or saving an altered value. Validate/normalize imported amounts
before filling a form. Both opt-ins accept a boolean or closure and can be disabled with `false`.
`nullable()` alone does not enable `emptyAsNull()`; blank-to-null behavior is independent of validation.

Integer cents and their formatted masks remain exact throughout the PHP integer range, including
values above JavaScript's safe integer limit. The browser mask uses `BigInt`. The legacy `Currency.masking()` helper separately retains numeric-unit
versus string-cent input semantics, `locales`, and the `options` object for `Intl.NumberFormat`
(including `minimumFractionDigits` / `maximumFractionDigits`). Current fields use the new mask directly.
Ordinary decimal
output keeps the legacy float type; amounts whose cents exceed `2^52` return an exact decimal
**string** instead. Use `intFormat()` with PHP integers or integer strings, or `dehydrateMask()`, when
exact large amounts are required. Do not pass large amounts as floats: their precision is already
lost before the field receives them; unsafe or non-finite floats are rejected.

#### Upgrading subclasses and published assets

Run `php artisan filament:assets` after upgrading. The asset is now `money` under
`leandrocfe/filament-ptbr-form-fields`; the previous `public/js/money-script.js` can be removed.
If no published asset exists, the field uses an inline copy of the same mask.

The protected `sanitizeState(?string $state): ?int`, `getOnKeyPress(): array`, `getOnKeyUp(): array`,
and `getOnBlur(): array` extension points remain available and are invoked. `sanitizeState()` applies
to default parsing; opting in to `decimalInput()` selects the new parser instead. The keyboard hooks
now return `keydown` and `input.capture` attributes by default; subclasses overriding them may keep
their attributes or migrate to those events to support paste, sign changes, and caret preservation.

#### Using Integer Values

If you prefer to work with integer values, you can format the money input using the `intFormat()` method:

```php
use Leandrocfe\FilamentPtbrFormFields\Money;
Money::make('price')
    ->default(10000)
    ->intFormat()

#output: 10000
```
#### Getting the Raw State

To retrieve the formatted state of the field, you can use the `dehydrateMask()` method:

```php
use Leandrocfe\FilamentPtbrFormFields\Money;
Money::make('price')
    ->default('100,00')
    ->dehydrateMask()

#output: 100,00
```

If you need to remove the prefix from the money input, simply pass null to the `prefix()` method:
```php
Money::make('price')
    ->prefix(null)
```
#### Currency Formatting

This package leverages the `archtechx/money` package under the hood. By default, it uses the `BRL` (Brazilian Real) format for currency values.

If you want to switch to the `USD` (United States Dollar) format, you can do so with the following code:
```php
use Leandrocfe\FilamentPtbrFormFields\Currencies\USD;

Money::make('price')
    ->currency(USD::class)
    ->prefix('$')
```

You can also define custom currencies to suit your specific needs:


```php

/*
 * app/Currencies/EUR.php
 */
 
declare(strict_types=1);

namespace App\Currencies;

use ArchTech\Money\Currency;

class EUR extends Currency
{
    /*
     * Code of the currency.
     */
    public string $code = 'EUR';

    /*
     * Name of the currency.
     */
    public string $name = 'Euro';

    /*
     * Rate of this currency relative to the default currency.
     */
    public float $rate = 1.0;

    /*
     * Number of decimals used in money calculations.
     */
    public int $mathDecimals = 2;

    /*
     * Number of decimals used in the formatted value
     */
    public int $displayDecimals = 2;

    /*
     * How many decimals of the currency's values should get rounded
     */
    public int $rounding = 2;

    /*
     * Prefix placed at the beginning of the formatted value.
    */
    public string $prefix = '€';

    /*
     * The language code.
     */
    public string $locale = 'pt';

    /*
     * The character used to separate the decimal values.
     */
    public string $decimalSeparator = '.';

    /*
     * The character used to separate groups of thousands
     */
    public string $thousandsSeparator = ',';
}

```

```php
use App\Currencies\EUR;

Money::make('price')
->currency(EUR::class)
->prefix('€')
```

### CEP (Brazilian ZIP Code)

The CEP field provides automatic address lookup through configurable providers like ViaCep and BrasilAPI.

#### Basic Usage

```php
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Leandrocfe\FilamentPtbrFormFields\Cep;
use Leandrocfe\FilamentPtbrFormFields\Enums\CepFieldMode;
use Leandrocfe\FilamentPtbrFormFields\Providers\ViaCepProvider;

Cep::make('postal_code')
    ->mode(CepFieldMode::SUFFIX) // or CepFieldMode::ON_BLUR
    ->api(ViaCepProvider::class, function (Set $set, ?array $response) {
        $set('street', data_get($response, 'logradouro'));
        $set('neighborhood', data_get($response, 'bairro'));
        $set('city', data_get($response, 'localidade'));
        $set('state', data_get($response, 'uf'));
    }),

TextInput::make('street'),
TextInput::make('neighborhood'),
TextInput::make('city'),
TextInput::make('state'),
```

#### Configuration

You can publish the configuration file to customize the default API endpoints:

```bash
php artisan vendor:publish --tag=filament-ptbr-form-fields-config
```

This will publish `config/filament-ptbr-form-fields.php`:

```php
return [
    'viacep_url' => env('VIACEP_URL', 'https://viacep.com.br/ws/'),
    'brasilapi_url' => env('BRASILAPI_URL', 'https://brasilapi.com.br/api/cep/v1/'),
];
```

You can also override the endpoints via environment variables in your `.env` file:

```dotenv
VIACEP_URL=https://viacep.com.br/ws/
BRASILAPI_URL=https://brasilapi.com.br/api/cep/v1/
```

> **Note:** The protocol (`https://`), host, and trailing slash (`/`) are required when specifying custom URLs.
> If your application uses configuration caching, make sure to run `php artisan config:clear` (or `php artisan config:cache`) after modifying environment variables or the configuration file.

#### Lookup Modes

**ON_BLUR (default)**: Automatically fetches address when the field loses focus
```php
Cep::make('postal_code')
    ->mode(CepFieldMode::ON_BLUR)
    ->api(ViaCepProvider::class, function (Set $set, ?array $response) {
        // ...
    })
```

**SUFFIX**: Shows a search button next to the field
```php
Cep::make('postal_code')
    ->mode(CepFieldMode::SUFFIX)
    ->api(ViaCepProvider::class, function (Set $set, ?array $response) {
        // ...
    })
```

#### Custom Error Message

```php
Cep::make('postal_code')
    ->errorMessage('Invalid CEP')
    ->api(ViaCepProvider::class, function (Set $set, ?array $response) {
        // ...
    })
```

#### Available Providers

- **ViaCepProvider**: Default provider using [ViaCep API](https://viacep.com.br/)
- **BrasilApiProvider**: Alternative provider using [BrasilAPI](https://brasilapi.com.br/)

```php
use Leandrocfe\FilamentPtbrFormFields\Providers\BrasilApiProvider;

Cep::make('cep')
    ->api(BrasilApiProvider::class, function (Set $set, ?array $response) {
        // ...
    })
```

#### Custom Provider

You can create your own provider by implementing `CepProviderInterface`:

```php
use Leandrocfe\FilamentPtbrFormFields\Providers\CepProviderInterface;
use Illuminate\Support\Collection;

class MyCustomProvider implements CepProviderInterface
{
    public function fetch(string $cep): null|Collection|array
    {
        // Your implementation
        return $response;
    }
}
```

#### Troubleshooting

##### `URI must include a scheme and host`

If you encounter the following exception when searching for a CEP:

```text
Illuminate\Http\Client\ConnectionException
URI must include a scheme and host. Use an absolute URI, a network-path reference starting with //, or configure a base_uri.
```

This occurs when the provider endpoint URL is missing the scheme (e.g., `viacep.com.br/ws/` instead of `https://viacep.com.br/ws/`).

To resolve this:
1. Ensure your `VIACEP_URL` or `BRASILAPI_URL` environment variables in `.env` include the full protocol: `https://viacep.com.br/ws/` or `https://brasilapi.com.br/api/cep/v1/`.
2. If you have published `config/filament-ptbr-form-fields.php`, verify that the default URLs include `https://`.
3. Clear the configuration cache by running:
   ```bash
   php artisan config:clear
   ```

#### Legacy Method (Deprecated)

The old `viaCep()` method is still available for backward compatibility but will be removed:

```php
Cep::make('postal_code')
    ->mode(CepFieldMode::SUFFIX)
    ->viaCep(
        mode: 'suffix',
        errorMessage: 'CEP inválido.',
        setFields: [
            'street' => 'logradouro',
            'district' => 'bairro',
            'city' => 'localidade',
            'state' => 'uf'
        ]
    )
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to <leandrocfe@gmail.com>.

## Credits

-   [Leandro Costa Ferreira](https://github.com/leandrocfe)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
