<?php

declare(strict_types=1);

return [
    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => app_path('Modules/CoreModule/Resources/Views'),
        'employees' => app_path('Modules/EmployeesModule/Livewire'),
        'schedule' => app_path('Modules/WfmModule/Livewire'),
    ],
];
