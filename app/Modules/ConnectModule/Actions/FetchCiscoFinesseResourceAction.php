<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use SimpleXMLElement;

class FetchCiscoFinesseResourceAction
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    /**
     * @param  array<string, scalar|array<int, scalar>|null>  $query
     * @return array<string, mixed>
     */
    public function execute(string $resource, array $query = []): array
    {
        $baseUrl = rtrim((string) config('contact-center.cisco.base_url', ''), '/');
        $username = (string) config('contact-center.cisco.username', '');
        $password = (string) config('contact-center.cisco.password', '');
        $timeout = max((int) config('contact-center.cisco.timeout', 15), 1);
        $verifySsl = filter_var(config('contact-center.cisco.verify_ssl', false), FILTER_VALIDATE_BOOL);

        if ($baseUrl === '' || $username === '' || $password === '') {
            throw new RuntimeException('La configuración de Cisco Finesse está incompleta.');
        }

        $endpoint = $baseUrl.'/'.ltrim($resource, '/');

        $response = $this->http
            ->accept('application/xml')
            ->withBasicAuth($username, $password)
            ->timeout($timeout)
            ->withOptions([
                'verify' => $verifySsl,
            ])
            ->get($endpoint, $query);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?? 0;
            $body = trim((string) $exception->response?->body());

            throw new RuntimeException(
                sprintf('Cisco Finesse respondió HTTP %d para [%s]. %s', $status, $resource, $body),
                previous: $exception,
            );
        }

        $contentType = strtolower((string) ($response->headers()['Content-Type'][0] ?? ''));

        if (str_contains($contentType, 'json')) {
            return $response->json() ?? [];
        }

        $body = trim($response->body());

        if ($body === '') {
            return [];
        }

        return $this->parseXml($body);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseXml(string $body): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if (! $xml instanceof SimpleXMLElement) {
            $errors = array_map(
                static fn ($error) => trim($error->message),
                libxml_get_errors(),
            );
            libxml_clear_errors();

            throw new RuntimeException('No fue posible interpretar la respuesta XML de Cisco Finesse: '.implode(' | ', $errors));
        }

        libxml_clear_errors();

        $parsed = $this->xmlNodeToArray($xml);

        return is_array($parsed)
            ? $parsed
            : ['value' => $parsed];
    }

    /**
     * @return array<string, mixed>|list<mixed>|string
     */
    private function xmlNodeToArray(SimpleXMLElement $node): array|string
    {
        $children = [];

        foreach ($node->attributes() as $attributeName => $attributeValue) {
            $children['@attributes'][(string) $attributeName] = trim((string) $attributeValue);
        }

        foreach ($node->children() as $child) {
            $name = $child->getName();
            $value = $this->xmlNodeToArray($child);

            if (array_key_exists($name, $children)) {
                $existing = $children[$name];
                $children[$name] = is_array($existing) && array_is_list($existing)
                    ? [...$existing, $value]
                    : [$existing, $value];

                continue;
            }

            $children[$name] = $value;
        }

        $text = trim((string) $node);

        if ($children === []) {
            return $text;
        }

        if ($text !== '' && ! array_key_exists('value', $children)) {
            $children['value'] = $text;
        }

        return $children;
    }
}
