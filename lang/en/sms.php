<?php

declare(strict_types=1);

return [

    'codes' => [
        '202' => 'Submitted successfully',
        '1001' => 'Invalid number',
        '1002' => 'Sender ID is not correct or is disabled',
        '1003' => 'Required fields missing — contact your system administrator',
        '1005' => 'Internal error at the SMS provider',
        '1006' => 'Balance validity not available',
        '1007' => 'Insufficient balance',
        '1011' => 'User ID not found — check the API key',
        '1012' => 'Masking SMS must be sent in Bangla',
        '1013' => 'Sender ID has no gateway for this API key',
        '1014' => 'Sender type name not found for this sender',
        '1015' => 'Sender ID has no valid gateway for this API key',
        '1016' => 'Sender type active price info not found',
        '1017' => 'Sender type price info not found',
        '1018' => 'The owner of this account is disabled',
        '1019' => 'The sender type price of this account is disabled',
        '1020' => 'The parent of this account was not found',
        '1021' => 'The parent active sender type price was not found',
        '1032' => 'Your server IP is not whitelisted — add it in the BulkSMSBD panel under Phonebook',

        'unknown' => 'No response code returned by the SMS provider',
        'unknown_code' => 'Unrecognised SMS provider code: :code',
    ],

    'disabled' => 'SMS sending is currently disabled.',
    'cap_reached' => 'The daily SMS cap has been reached.',
    'balance_unavailable' => 'Balance is unavailable.',

    'estimate' => [
        'unicode_notice' => 'This message contains Bangla, so it is sent as Unicode: :segments segment(s) per message at 70 characters each, instead of 160.',
        'latin_notice' => 'This message is Latin-only: :segments segment(s) per message at 160 characters each.',
        'total' => ':total segments across :recipients recipient(s).',
    ],

];
