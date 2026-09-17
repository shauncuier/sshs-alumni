<?php

declare(strict_types=1);

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes, rendered as inline SVG.
 *
 * SVG rather than PNG for two reasons: it needs no imagick or GD extension —
 * one less thing to be missing on the production host on event day — and it
 * stays sharp when a member zooms into their phone screen at the gate, which
 * is exactly when a blurry code costs someone their place in the queue.
 *
 * Error correction is fixed at QUARTILE. A membership card lives in a wallet
 * and an event pass gets screenshotted, cropped and re-photographed; the
 * default LOW level gives up too easily on a scuffed or partly covered code.
 *
 * @see docs/05-modules.md section 16
 */
final class Qr
{
    /**
     * The QR payload is always a URL that the SERVER resolves. Nothing about
     * a member or a registration is encoded in the code itself, so a forged
     * code can only ever point at a real record or at nothing.
     */
    public static function svg(string $payload, int $size = 240, int $margin = 1): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle($size, $margin),
                new SvgImageBackEnd,
            ),
        );

        return $writer->writeString($payload);
    }

    /**
     * The same SVG as a data URI, for embedding directly in an <img> or in a
     * PDF without a second request.
     */
    public static function dataUri(string $payload, int $size = 240, int $margin = 1): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($payload, $size, $margin));
    }
}
