<?php

declare(strict_types=1);

namespace App\Services\Media;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Strips executable content from an SVG before it is stored.
 *
 * SVG is XML, so an uploaded logo can carry <script>, event handlers, external
 * entities and links to remote resources. Served from our own origin, that is
 * stored XSS. SVG is therefore accepted only for sponsor logos, and only after
 * passing through here.
 *
 * @see docs/08-security-privacy.md section 5
 */
final class SvgSanitizer
{
    /**
     * Elements that can execute, load remote content, or reference local
     * files.
     *
     * @var array<int, string>
     */
    private const FORBIDDEN_ELEMENTS = [
        'script', 'foreignobject', 'iframe', 'embed', 'object', 'audio',
        'video', 'handler', 'set', 'animate', 'animatetransform', 'use',
    ];

    /**
     * @var array<int, string>
     */
    private const FORBIDDEN_ATTRIBUTE_PREFIXES = ['on', 'xlink:href', 'href'];

    public static function clean(string $svg): string
    {
        // Strip XML declarations carrying entity definitions, which is how
        // XXE payloads reach a parser.
        $svg = (string) preg_replace('/<!DOCTYPE.*?>/is', '', $svg);
        $svg = (string) preg_replace('/<!ENTITY.*?>/is', '', $svg);

        $document = new DOMDocument;

        // Entity loading is disabled outright; nothing in a logo needs it.
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOENT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            // Unparseable input is not made safe by guessing at it.
            return '';
        }

        self::scrub($document);

        return (string) $document->saveXML();
    }

    private static function scrub(DOMNode $node): void
    {
        // Iterate over a snapshot: removing nodes mutates the live list.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->nodeName), self::FORBIDDEN_ELEMENTS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::scrubAttributes($child);
            self::scrub($child);
        }
    }

    private static function scrubAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = strtolower(trim($attribute->nodeValue ?? ''));

            foreach (self::FORBIDDEN_ATTRIBUTE_PREFIXES as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    $element->removeAttribute($attribute->nodeName);

                    continue 2;
                }
            }

            // javascript:, data: and remote URLs in any attribute.
            if (preg_match('/(javascript:|data:text|data:image\/svg|vbscript:)/i', $value) === 1) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }
}
