<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * SVG QR codes (`bacon/bacon-qr-code`): document verification links
 * (module 9.17) and two-factor setup (report 52).
 */
class QrCode
{
    public static function svg(string $text, int $size = 120, int $margin = 1): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd)))->writeString($text);
    }

    /** A data URI, for `<img src>` in PDFs and pages. */
    public static function dataUri(string $text, int $size = 120, int $margin = 1): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($text, $size, $margin));
    }
}
