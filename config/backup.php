<?php

return [
    'photos_path' => storage_path('app/public/products'),
    'destination' => storage_path('app/private/backups'),
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 7),
    'rclone_remote' => env('BACKUP_RCLONE_REMOTE'),
    'timezone' => 'Asia/Jakarta',
];
