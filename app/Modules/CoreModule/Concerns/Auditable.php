<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use App\Shared\Concerns\Auditable as SharedAuditable;

/**
 * Alias BC hacia App\Shared\Concerns\Auditable.
 *
 * @deprecated Usar App\Shared\Concerns\Auditable directamente. Este trait se mantiene para no romper
 *             imports existentes hasta completar la migración modular.
 */
trait Auditable
{
    use SharedAuditable;
}
