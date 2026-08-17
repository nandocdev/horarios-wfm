<?php

declare(strict_types=1);

namespace App\Shared\Support;

use DOMElement;
use DOMNode;
use Masterminds\HTML5;

/**
 * Sanitizador de HTML que elimina etiquetas y atributos peligrosos
 * antes de que el contenido generado por el usuario se persista o se renderice.
 */
final class HtmlSanitizer
{
    /**
     * Etiquetas bloqueadas por completo; se elimina el nodo y su contenido.
     */
    private const BLOCKED_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'link',
        'meta',
        'noscript',
        'video',
        'audio',
        'source',
    ];

    public static function sanitize(string $html): string
    {
        $html5 = new HTML5;
        $fragment = $html5->loadHTMLFragment($html);

        self::cleanNode($fragment);

        return $html5->saveHTML($fragment);
    }

    private static function cleanNode(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                if (in_array(strtolower($child->tagName), self::BLOCKED_TAGS, true)) {
                    $parent->removeChild($child);

                    continue;
                }

                self::stripDangerousAttributes($child);
                self::cleanNode($child);
            }
        }
    }

    private static function stripDangerousAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = strtolower(trim($attribute->nodeValue));

            if (str_starts_with($name, 'on')
                || str_starts_with($value, 'javascript:')
                || str_starts_with($value, 'vbscript:')
                || str_starts_with($value, 'data:text/html')) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }
}
