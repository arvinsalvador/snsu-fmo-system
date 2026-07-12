<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->is('api/v1/*') || ! $response instanceof JsonResponse || $response->getStatusCode() === 204 || $response->getStatusCode() >= 400) {
            return $response;
        }$payload = $response->getData(true);
        if (! is_array($payload)) {
            return $response;
        }$normalized = ['success' => $payload['success'] ?? true, 'message' => $payload['message'] ?? 'Request completed successfully.', 'data' => array_key_exists('data', $payload) ? $payload['data'] : $payload, 'meta' => $payload['meta'] ?? null];
        if (array_key_exists('links', $payload)) {
            $normalized['links'] = $payload['links'];
        }foreach (array_diff_key($payload, array_flip(['success', 'message', 'data', 'meta', 'links'])) as $key => $value) {
            $normalized[$key] = $value;
        }$response->setData($normalized);

        return $response;
    }
}
