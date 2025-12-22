<?php

return [
    'api_url' => 'https://merchant.revolut.com/api',
    'api_key' => getenv('REVOLUT_API_KEY') ?: 'mock_api_key',
    'webhook_secret' => getenv('REVOLUT_WEBHOOK_SECRET') ?: 'mock_webhook_secret',
    'is_mock' => getenv('REVOLUT_IS_MOCK') !== 'false', // Default to mock for development
    'currency' => 'EUR',
    'success_url' => 'https://balikpro.sk/checkout/success',
    'failure_url' => 'https://balikpro.sk/checkout/failure',
];
