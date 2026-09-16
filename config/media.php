<?php

declare(strict_types=1);

use App\Enums\MediaCollection;

return [

    /*
    |---------------------------------------------------------------------
    | Disks
    |---------------------------------------------------------------------
    |
    | `private` files are NEVER served by a direct URL. They go through a
    | controller that runs a policy check first. Verification documents and
    | sponsor agreements always land there.
    |
    */

    'public_disk' => env('MEDIA_PUBLIC_DISK', 'public'),
    'private_disk' => env('MEDIA_PRIVATE_DISK', 'local'),

    /*
    |---------------------------------------------------------------------
    | Per-collection rules
    |---------------------------------------------------------------------
    |
    | `max_kb` is enforced in validation; `mimes` is checked against the
    | file's REAL type, never the client-supplied Content-Type.
    |
    | `max_dimension` caps the longest edge, which also defuses
    | decompression-bomb images.
    |
    */

    'collections' => [

        MediaCollection::Profile->value => [
            'disk' => 'public',
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 2048,
            'max_dimension' => 1200,
            'thumb' => 300,
        ],

        MediaCollection::Gallery->value => [
            'disk' => 'public',
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 5120,
            'max_dimension' => 2000,
            'thumb' => 480,
        ],

        MediaCollection::Cover->value => [
            'disk' => 'public',
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 5120,
            'max_dimension' => 2400,
            'thumb' => 800,
        ],

        MediaCollection::Banner->value => [
            'disk' => 'public',
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 5120,
            'max_dimension' => 2400,
            'thumb' => 800,
        ],

        MediaCollection::Logo->value => [
            'disk' => 'public',
            // SVG is accepted for sponsor logos only, and is sanitised before
            // storage — scripts, external references and event handlers are
            // stripped.
            'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'svg'],
            'max_kb' => 2048,
            'max_dimension' => 1000,
            'thumb' => 300,
        ],

        MediaCollection::Document->value => [
            'disk' => 'private',
            'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
            'max_kb' => 5120,
            'max_dimension' => null,
            'thumb' => null,
        ],

        MediaCollection::Attachment->value => [
            'disk' => 'private',
            'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
            'max_kb' => 10240,
            'max_dimension' => null,
            'thumb' => null,
        ],

    ],

];
