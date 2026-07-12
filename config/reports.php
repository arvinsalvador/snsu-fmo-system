<?php

return [
    'disk' => env('REPORT_STORAGE_DISK', 'local'),
    'retention_days' => env('REPORT_RETENTION_DAYS'),
    'detail_record_limit' => 100,
    'external_email_recipients' => false,
];
