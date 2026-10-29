/**
 * Brazilian / locale money mask used by FilamentPtbrFormFields\Money.
 *
 * Digits are treated as a calculator/ATM input: 100020 → 1.000,20 in pt-BR.
 * The function is idempotent for already-masked values that include the
 * currency's fraction digits.
 *
 * This file is also inlined into an HTML attribute by Money.php when the asset
 * has not been published, so every statement is terminated explicitly: it must
 * stay valid even if newlines are collapsed.
 */
(function (global) {
    'use strict';

    function maskMoney(value, options) {
        const locale = options?.locale ?? 'pt-BR';
        const fractionDigits = options?.fractionDigits ?? 2;
        const empty = options?.empty ?? false;

        const raw = String(value ?? '');
        const negative = raw.includes('-');

        const digits = raw
            .replace(/\D/g, '')
            .replace(/^0+(?=\d)/, '');

        if (empty && digits === '') {
            return negative ? '-' : '';
        }

        const padded = digits.padStart(fractionDigits + 1, '0');
        const integer = fractionDigits === 0 ? padded : padded.slice(0, -fractionDigits) || '0';
        const fraction = fractionDigits === 0 ? '' : padded.slice(-fractionDigits);
        // Intl formats BigInt exactly, including the fixed fraction,
        // which is localized independently and never converted to Number.
        const formatter = new Intl.NumberFormat(locale, {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits,
        });
        const nonzero = /[1-9]/.test(padded);
        const whole = BigInt(integer);
        const sign = negative && nonzero;
        const parts = formatter.formatToParts(sign && whole !== 0n ? -whole : whole);
        const fractionPart = fractionDigits === 0 ? '' : new Intl.NumberFormat(locale, {
            useGrouping: false,
            minimumIntegerDigits: fractionDigits,
            maximumFractionDigits: 0,
        }).format(BigInt(fraction));
        const formatted = parts.map((part) => part.type === 'fraction' ? fractionPart : part.value).join('');

        return sign && whole === 0n ? formatter.formatToParts(-1n).map((part) => {
            if (part.type === 'integer') { return new Intl.NumberFormat(locale).format(0); }
            if (part.type === 'fraction') { return fractionPart; }
            return part.value;
        }).join('') : formatted;
    }

    function applyMask(input, options) {
        if (! (input instanceof HTMLInputElement)) {
            return '';
        }

        const selectionStart = input.selectionStart;
        const selectionEnd = input.selectionEnd;
        const selectionDirection = input.selectionDirection;
        const digitsAfter = (position) => (input.value.slice(position).match(/\d/g) || []).length;
        const startDigitsAfter = selectionStart === null ? null : digitsAfter(selectionStart);
        const endDigitsAfter = selectionEnd === null ? null : digitsAfter(selectionEnd);
        const masked = maskMoney(input.value, options);
        input.value = masked;

        try {
            const positionBeforeTrailingDigits = (digitCount) => {
                if (digitCount <= 0) {
                    return masked.length;
                }

                let seen = 0;

                for (let index = masked.length - 1; index >= 0; index -= 1) {
                    if (/\d/.test(masked[index])) {
                        seen += 1;

                        if (seen === digitCount) {
                            return index;
                        }
                    }
                }

                return masked.startsWith('-') ? 1 : 0;
            };

            const start = startDigitsAfter === null ? masked.length : positionBeforeTrailingDigits(startDigitsAfter);
            const end = endDigitsAfter === null ? start : positionBeforeTrailingDigits(endDigitsAfter);
            input.setSelectionRange(start, end, selectionDirection || 'none');
        } catch (error) {
            /* selection is unsupported for some input types */
        }

        return masked;
    }

    function handleKeydown(input, event, options) {
        if (! (input instanceof HTMLInputElement) || input.readOnly || input.disabled || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (event.key === '-') {
            event.preventDefault();

            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? start;
            const direction = input.selectionDirection || 'none';
            const togglingOff = input.value.startsWith('-');
            const offset = togglingOff ? -1 : 1;

            input.value = togglingOff ? input.value.slice(1) : `-${input.value}`;

            try {
                input.setSelectionRange(
                    Math.max(0, Math.min(input.value.length, start + offset)),
                    Math.max(0, Math.min(input.value.length, end + offset)),
                    direction,
                );
            } catch (error) {
                /* selection is unsupported for some input types */
            }

            applyMask(input, options);
            input.dispatchEvent(new Event('input', { bubbles: true }));

            return;
        }

        if (event.key === 'Backspace' || event.key === 'Delete') {
            const start = input.selectionStart;
            const end = input.selectionEnd;

            if (start === null || start !== end) {
                return;
            }

            const backspace = event.key === 'Backspace';
            const adjacentIndex = backspace ? start - 1 : start;
            const adjacent = input.value[adjacentIndex];

            if (adjacent === undefined || /\d/.test(adjacent) || adjacent === '-') {
                return;
            }

            const step = backspace ? -1 : 1;
            let digitIndex = adjacentIndex;

            while (digitIndex >= 0 && digitIndex < input.value.length && ! /\d/.test(input.value[digitIndex])) {
                digitIndex += step;
            }

            if (digitIndex < 0 || digitIndex >= input.value.length) {
                return;
            }

            event.preventDefault();
            input.value = input.value.slice(0, digitIndex) + input.value.slice(digitIndex + 1);

            try {
                const position = start - (digitIndex < start ? 1 : 0);
                input.setSelectionRange(position, position);
            } catch (error) {
                /* selection is unsupported for some input types */
            }

            applyMask(input, options);
            input.dispatchEvent(new Event('input', { bubbles: true }));

            return;
        }

        if (event.key.length === 1 && ! /[0-9]/.test(event.key)) {
            event.preventDefault();
        }
    }

    const api = { maskMoney, applyMask, handleKeydown };

    global.FilamentPtbrFormFields = Object.assign(global.FilamentPtbrFormFields || {}, api);

    /* Legacy helper keeps its original number/string and Intl option semantics.
     * Current fields call the exact BigInt mask above directly. */
    global.Currency = global.Currency || {
        masking(value, opts = {}) {
            const {
                empty = false,
                locales = 'pt-BR',
                options = { minimumFractionDigits: 2 },
            } = opts;
            const input = typeof value === 'number' ? value.toFixed(2) : String(value);
            const digits = input.replace(/\D/g, '').replace(/^0+/g, '');
            const padded = digits.padStart(3, '0');
            const fraction = padded.slice(-2);
            const integer = padded.slice(0, -2);

            if (empty && integer === '0' && fraction === '00') {
                return '';
            }

            return new Intl.NumberFormat(locales, options).format(`${integer}.${fraction}`);
        },
    };
})(typeof window !== 'undefined' ? window : globalThis);
