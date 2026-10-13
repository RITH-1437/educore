<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Two-factor sign-in (report 52). */
#[OA\Schema(
    schema: 'TwoFactorStatus',
    type: 'object',
    properties: [
        new OA\Property(property: 'enabled', type: 'boolean'),
        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'recovery_codes_left', type: 'integer'),
        new OA\Property(property: 'setup', type: 'object', nullable: true, description: 'Only while a setup is pending.', properties: [
            new OA\Property(property: 'secret', type: 'string', description: 'Base32, in groups of four, for typing into the app.'),
            new OA\Property(property: 'otpauth_url', type: 'string', example: 'otpauth://totp/EduCore:user%40example.edu?secret=…&issuer=EduCore&algorithm=SHA1&digits=6&period=30'),
            new OA\Property(property: 'qr_svg', type: 'string', description: 'SVG markup of the QR code for `otpauth_url`.'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'TwoFactorRecoveryCodes',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'object', properties: [
            new OA\Property(property: 'recovery_codes', type: 'array', items: new OA\Items(type: 'string', example: 'k3f9a-x2m7q'), description: 'Eight single-use codes, shown only in this response.'),
        ]),
    ]
)]
class TwoFactor {}
