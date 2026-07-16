<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'provider' => Env::string('LPR_PROVIDER', 'mock'),
    'upload_directory' => Env::string('LPR_UPLOAD_DIRECTORY', 'storage/uploads/lpr'),
    'maximum_upload_bytes' => Env::integer('LPR_MAX_UPLOAD_BYTES', 5 * 1024 * 1024),
    'confidence_threshold' => (float) Env::string('LPR_CONFIDENCE_THRESHOLD', '0.80'),
    'mock' => [
        'recognized_text' => Env::string('LPR_MOCK_TEXT', '59A12345'),
        'confidence' => (float) Env::string('LPR_MOCK_CONFIDENCE', '0.96'),
    ],
];
