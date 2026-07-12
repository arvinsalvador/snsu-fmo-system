<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (! $key || in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }abort_if(strlen($key) > 100, 422, 'Idempotency-Key may not exceed 100 characters.');
        $hash = hash('sha256', $request->getContent().json_encode($request->except(['file', 'photo', 'image'])));
        $record = DB::table('api_idempotency_keys')->where('user_id', $request->user()->id)->where('key', $key)->first();
        if ($record) {
            abort_if($record->method !== $request->method() || $record->path !== $request->path() || $record->request_hash !== $hash, 409, 'Idempotency key was already used for a different request.');
            abort_if(! $record->completed_at, 409, 'An identical request is still processing.');

            return response($record->response_body, $record->response_status, ['Content-Type' => 'application/json', 'Idempotency-Replayed' => 'true']);
        }DB::table('api_idempotency_keys')->insert(['user_id' => $request->user()->id, 'key' => $key, 'method' => $request->method(), 'path' => $request->path(), 'request_hash' => $hash, 'created_at' => now(), 'updated_at' => now()]);
        $response = $next($request);
        if ($response->getStatusCode() < 400 && str_contains((string) $response->headers->get('Content-Type'), 'json')) {
            DB::table('api_idempotency_keys')->where('user_id', $request->user()->id)->where('key', $key)->update(['response_status' => $response->getStatusCode(), 'response_body' => $response->getContent(), 'completed_at' => now(), 'updated_at' => now()]);
        } else {
            DB::table('api_idempotency_keys')->where('user_id', $request->user()->id)->where('key', $key)->delete();
        }

        return $response;
    }
}
