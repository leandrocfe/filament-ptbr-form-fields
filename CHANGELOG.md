# Changelog

All notable changes to `filament-ptbr-form-fields` will be documented in this file.

## 5.1.0 (2026-10-29)

### Added
- Alphanumeric CNPJ masks in `Document::cnpj()` and `dynamic()`, including the deprecated `PtbrCpfCnpj` API. Classic numeric masks remain available via `alphanumeric: false`; custom masks and constants remain supported.
- `Money::decimalInput()` opts in to decimal-unit parsing for numeric values and canonical decimal strings. Ambiguous strings and unsupported fractional precision raise `InvalidArgumentException`; accepted values are never silently cleared.
- `Money::emptyAsNull()` opts in to blank display and null dehydration. Both new options accept booleans or closures.

### Fixed
- Deprecated `PtbrMoney` uses quote-safe Alpine handlers, preventing truncated HTML attributes and initialization errors while preserving legacy formatting and dehydration.
- Money field formatting in Filament 5 ([#79](https://github.com/leandrocfe/filament-ptbr-form-fields/issues/79)): the mask handles input, paste, negative signs, zero-decimal currencies, caret/selection retention, and separator deletion. Sign/deletion changes dispatch bubbling input events for Livewire.
- Readonly and disabled Money inputs cannot be changed by the custom keyboard handler.
- The legacy `Currency.masking()` helper preserves number-versus-string semantics (`100` → `100,00`, `"100"` → `1,00`), locale selection, and custom `Intl.NumberFormat` options. Current fields use the exact BigInt mask directly.
- Integer cents and formatted masks retain exact precision throughout PHP's integer range; JavaScript formats large values with BigInt. Decimal output above `2^52` cents is returned as an exact string, keeping a conservative rounding margin after scaling to decimal units. Overflow raises an explicit exception.
- `currency(BRL::class)` retains the `R$` prefix. `dehydrateMask()` and `intFormat()` closures are evaluated.
- Document state is normalized to uppercase during hydration, schema validation, and dehydration, including when keeping mask punctuation. Live typing callbacks may still receive lowercase input.

### Changed
- `laravellegends/pt-br-validator` now requires `^12.2 || ^13.0`, ensuring alphanumeric CNPJ validation support. PHP 8.2+ and Filament 5 remain required.
- Money listens to `input.capture` and `keydown` by default. The protected `sanitizeState`, `getOnKeyPress`, `getOnKeyUp`, and `getOnBlur` extension points remain available and invoked; keyboard hook implementations now supply the new events.
- Money assets use the package namespace `leandrocfe/filament-ptbr-form-fields` and id `money`. The inline fallback reads the same `resources/js/money.js` implementation.
- The service provider extends Spatie's `PackageServiceProvider` and registers assets during package boot.

### Upgrade notes
- Default Money input and empty semantics remain compatible with 5.0.1: `1.230` is 1230 cents (`12,30`), integer `1000` is 1000 cents, and blank/null saves zero. No database nullability change is needed for the default behavior.
- Applications wanting decimal units should add `decimalInput()` explicitly. In that mode, `1000.20` and `1000.2000` represent 1000.2 units; `1.230` is ambiguous and must be normalized to `1.23` or `1.230,00`. `1.000` is an explicit thousands convention in this mode. For nullable output, also add `emptyAsNull()` and ensure the receiving column accepts null.
- For exact large amounts, use integer-cent inputs and `intFormat()` or `dehydrateMask()`. Decimal output above `2^52` cents is a string. Floats cannot convey exact large amounts and are rejected by the decimal parser when unsafe.
- Subclasses may retain the four protected hooks. `sanitizeState()` governs the default legacy parser; `decimalInput()` bypasses it. Prefer `keydown` / `input.capture` in custom keyboard hooks when adopting the new interaction handling.
- Run `php artisan filament:assets` again. The previous `public/js/money-script.js` is orphaned and may be deleted. The inline fallback applies only when the new asset has not been published.
- Applications pinned to validator 10.x, 11.x, or 12.0–12.1 must upgrade to a supported constraint. `alphanumeric: false` limits the mask, while the validator still accepts valid alphanumeric CNPJ payloads.
- CNPJ dehydration uppercases letters even without `dehydrateMask()`. Existing numeric CNPJs remain supported.
