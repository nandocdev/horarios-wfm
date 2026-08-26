<?php

declare(strict_types=1);

namespace App\Shared\Concerns;

/**
 * Trait para atributos PII cifrados en reposo.
 *
 * Uso: define $encrypted = ['phone','mobile_phone','citizen_identifier'] en tu modelo
 * y declara casts 'encrypted' de Laravel 13. El trait valida que los campos existan
 * y ofrece helpers para rotación futura.
 *
 * Ejemplo:
 *   use HasEncryptedAttributes;
 *   protected array $encrypted = ['phone','citizen_identifier'];
 *   protected function casts(): array { return ['phone' => 'encrypted']; }
 */
trait HasEncryptedAttributes
{
    /**
     * Campos que deben cifrarse. Sobrescribe en el modelo.
     *
     * @var array<int, string>
     */
    protected array $encrypted = [];

    /**
     * Retorna los atributos cifrados declarados.
     *
     * @return array<int, string>
     */
    public function getEncryptedAttributes(): array
    {
        return $this->encrypted ?? [];
    }

    /**
     * Verifica si un atributo está marcado como cifrado.
     */
    public function isEncryptedAttribute(string $key): bool
    {
        return in_array($key, $this->getEncryptedAttributes(), true);
    }

    /**
     * Hook inicial: valida que los casts coincidan con $encrypted en non-production.
     */
    public function initializeHasEncryptedAttributes(): void
    {
        if (app()->environment('local', 'testing')) {
            foreach ($this->getEncryptedAttributes() as $attr) {
                $casts = $this->getCasts();
                if (! isset($casts[$attr]) || $casts[$attr] !== 'encrypted') {
                    // No lanzo excepción para no romper seeders; solo warning
                    logger()->warning("HasEncryptedAttributes: '{$attr}' declared in \$encrypted but missing 'encrypted' cast on ".static::class);
                }
            }
        }
    }
}
