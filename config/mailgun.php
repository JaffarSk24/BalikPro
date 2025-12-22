<?php
return [
    'api_key'      => getenv('MAILGUN_API_KEY'),
    'domain'       => getenv('MAILGUN_DOMAIN'),
    'api_endpoint' => rtrim(getenv('MAILGUN_API_ENDPOINT'), '/'),
    'is_mock'      => getenv('MAILGUN_IS_MOCK') === 'true',
    'from'         => 'Balík PRO <noreply@' . getenv('MAILGUN_DOMAIN') . '>'
];