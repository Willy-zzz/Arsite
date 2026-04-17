<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiAuditLogger
{
    /**
     * Keys that should never be written verbatim to logs.
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'remember_token',
        'authorization',
        'secret',
        'api_key',
    ];

    public static function info(string $message, ?Request $request = null, array $context = []): void
    {
        Log::info($message, self::buildContext($request, $context));
    }

    public static function warning(string $message, ?Request $request = null, array $context = []): void
    {
        Log::warning($message, self::buildContext($request, $context));
    }

    public static function error(
        string $message,
        ?Request $request = null,
        array $context = [],
        ?Throwable $exception = null
    ): void {
        $payload = self::buildContext($request, $context);

        if ($exception) {
            $payload['exception_class'] = $exception::class;
            $payload['exception_message'] = $exception->getMessage();
            $payload['exception_line'] = $exception->getLine();
        }

        Log::error($message, $payload);
    }

    public static function buildContext(?Request $request = null, array $context = []): array
    {
        $baseContext = [
            'user_id' => $request?->user()?->id,
            'user_role' => $request?->user()?->usu_rol,
            'ip' => $request?->ip(),
            'method' => $request?->method(),
            'route' => $request?->route()?->uri() ?? $request?->path(),
            'path' => $request?->path(),
        ];

        return array_filter(
            array_merge($baseContext, self::sanitizeContext($context)),
            static fn ($value) => !is_null($value)
        );
    }

    public static function storageContext(
        string $disk,
        ?string $path,
        ?UploadedFile $file = null,
        array $extra = []
    ): array {
        return array_merge([
            'disk' => $disk,
            'file_path' => $path,
            'file_name' => $path ? basename($path) : null,
            'original_name' => $file?->getClientOriginalName(),
            'file_size' => $file?->getSize(),
            'mime_type' => $file?->getClientMimeType(),
        ], $extra);
    }

    public static function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if ($value instanceof UploadedFile) {
                $sanitized[$key] = [
                    'original_name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'mime_type' => $value->getClientMimeType(),
                ];
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
                continue;
            }

            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $sanitized[$key] = self::sanitizeContext($value->toArray());
                }

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
