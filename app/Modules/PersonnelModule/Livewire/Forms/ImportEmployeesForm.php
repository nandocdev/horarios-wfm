<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ImportEmployeesForm extends Form
{
    #[Validate(['required', 'file', 'mimes:csv,txt', 'max:20480'])]
    public ?TemporaryUploadedFile $csv = null;

    #[Validate(['required', 'integer', 'min:100', 'max:1000'])]
    public int $chunk_size = 1000;
}
