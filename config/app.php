<?php

return [
    'name' => 'Balík PRO',
    'domain' => 'balikpro.sk',
    'debug' => getenv('APP_DEBUG') === 'true',
    'timezone' => 'Europe/Bratislava',
    'default_locale' => 'sk',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'balik-pro-jwt-secret-key-change-in-production',
    'jwt_ttl' => 8 * 3600, // 8 hours
    'superadmin_email' => 'roccreate@gmail.com',
    'email' => [
        'from_address' => 'noreply@balikpro.sk',
        'from_name' => 'Balík PRO',
    ],
    'uploads_path' => '/home/ubuntu/balik_pro_mvp/public/uploads/',
    'pdfs_path' => '/home/ubuntu/balik_pro_mvp/storage/pdfs/',
    'logs_path' => '/home/ubuntu/balik_pro_mvp/storage/logs/',
];
