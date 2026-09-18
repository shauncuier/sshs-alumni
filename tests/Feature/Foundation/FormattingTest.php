<?php

declare(strict_types=1);

/**
 * A year is not a quantity.
 *
 * `formatNumber` groups thousands, so a year passed through it renders as
 * "1,976". That shipped on the public footer — "The School: 1,976" — and read
 * as a count of something rather than a date. `formatYear` exists to say which
 * of the two a number is.
 *
 * This walks the frontend for the mistake rather than testing the helper,
 * because the helper was never wrong; the call site was.
 *
 * @see resources/js/lib/format.ts
 */
it('never renders a year through the thousands formatter', function (): void {
    $offenders = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('js')),
    );

    foreach ($files as $file) {
        if (! $file->isFile() || ! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        // formatNumber(...) wrapping anything that names a year.
        if (preg_match('/formatNumber\(\s*[^)]*(year|established|founded)[^)]*\)/i', $contents) === 1) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([]);
});

it('has a year formatter to use instead', function (): void {
    $format = (string) file_get_contents(resource_path('js/lib/format.ts'));

    expect($format)->toContain('export function formatYear');
});
