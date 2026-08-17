<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Especialidad (servicio) ofrecida en una puerta/consultorio de un piso,
 * con su horario de atención y el contacto del departamento.
 *
 * @property int $id
 * @property int $unit_id
 * @property string $name
 * @property string|null $door_id
 * @property string $attention_hours
 * @property string|null $results_hours
 * @property string $contact_role
 * @property string $contact_extension
 * @property string|null $contact_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Unit $unit
 */
class DirectoryService extends Model
{
    protected $table = 'directory_services';

    protected $fillable = [
        'unit_id',
        'name',
        'door_id',
        'attention_hours',
        'results_hours',
        'contact_role',
        'contact_extension',
        'contact_email',
    ];

    /**
     * Piso donde se ofrece el servicio.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
