<?php

declare(strict_types=1);

return [
    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => app_path('Modules/CoreModule/Resources/Views'),
        'employees' => app_path('Modules/EmployeesModule/Livewire'),
        'schedule' => app_path('Modules/WfmModule/Livewire'),
    ],

    'temporary_file_upload' => [
        'disk' => null,        // Defaults to 'local'
        'rules' => 'file|max:102400', // 100MB max global para archivos temporales
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5, // 5 minutes
    ],
];
