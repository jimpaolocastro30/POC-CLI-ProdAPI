<?php

return [
    'mfa_otp_expiry_minutes' => (int) env('MFA_OTP_EXPIRY_MINUTES', 10),
    'gcp' => [
        'project_id' => env('GCP_PROJECT_ID'),
        'storage_bucket' => env('GCP_STORAGE_BUCKET', 'inventory-documents'),
        'pubsub_topic' => env('GCP_PUBSUB_TOPIC', 'inventory-events'),
    ],
];
