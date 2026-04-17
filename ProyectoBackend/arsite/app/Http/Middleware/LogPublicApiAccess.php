<?php

namespace App\Http\Middleware;

use App\Support\ApiAuditLogger;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPublicApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);

        ApiAuditLogger::info('Consumo de API pública', $request, [
            'event' => 'public_api.access',
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'result_count' => $this->extractResultCount($response),
        ]);

        return $response;
    }

    private function extractResultCount(Response $response): ?int
    {
        if (!$response instanceof JsonResponse) {
            return null;
        }

        $payload = $response->getData(true);

        if (!is_array($payload) || !array_key_exists('data', $payload)) {
            return null;
        }

        if (is_array($payload['data']) && array_is_list($payload['data'])) {
            return count($payload['data']);
        }

        if (
            isset($payload['data']['data']) &&
            is_array($payload['data']['data']) &&
            array_is_list($payload['data']['data'])
        ) {
            return count($payload['data']['data']);
        }

        return null;
    }
}
