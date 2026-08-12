<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    /**
     * Routes jinhe log nahi karna hai.
     */
    protected array $excludedRoutes = [
        'activity-logs.index',
        'activity-logs.show',
    ];

    /**
     * Sensitive fields kabhi log nahi honge.
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'token',
        '_token',
        'api_token',
        'access_token',
        'secret',
        'otp',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        try {
            $this->logRequest($request, $response, $startedAt);
        } catch (\Throwable $e) {
            /*
             * Activity log fail hone ki wajah se
             * original application request fail nahi honi chahiye.
             */
            report($e);
        }

        return $response;
    }

    protected function logRequest(
        Request $request,
        Response $response,
        float $startedAt
    ): void {
        if (!auth()->check()) {
            return;
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, $this->excludedRoutes, true)) {
            return;
        }

        /*
         * Laravel debug/internal requests ignore.
         */
        if (
            $request->is('_debugbar/*') ||
            $request->is('livewire/*') ||
            $request->is('storage/*')
        ) {
            return;
        }

        $user = auth()->user();

        $duration = round(
            (microtime(true) - $startedAt) * 1000,
            2
        );

        $method = strtoupper($request->method());

        $action = $this->getActionName($request);

        $requestData = $this->sanitizeInput(
            $request->except($this->sensitiveFields)
        );

        $routeParameters = [];

        if ($request->route()) {
            foreach ($request->route()->parameters() as $key => $value) {
                if (is_object($value)) {
                    $routeParameters[$key] = [
                        'id' => $value->id ?? null,
                        'type' => get_class($value),
                    ];
                } else {
                    $routeParameters[$key] = $value;
                }
            }
        }

        activity('user-activity')
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,

                'user_name' => $user->name ?? null,

                'user_email' => $user->email ?? null,

                'method' => $method,

                'action' => $action,

                'route' => $routeName,

                'controller_action' =>
                    $request->route()?->getActionName(),

                'url' => $request->fullUrl(),

                'path' => $request->path(),

                'ip_address' => $request->ip(),

                'user_agent' => $request->userAgent(),

                'status_code' => $response->getStatusCode(),

                'duration_ms' => $duration,

                'request_data' => $requestData,

                'route_parameters' => $routeParameters,

                'referer' => $request->headers->get('referer'),

                'logged_at' => now()->toDateTimeString(),
            ])
            ->log($action);
    }

    protected function getActionName(Request $request): string
    {
        $method = strtoupper($request->method());

        $routeName = $request->route()?->getName();

        $routeName = $routeName ?: $request->path();

        return match ($method) {
            'GET' => "Viewed {$routeName}",
            'POST' => "Created / Action {$routeName}",
            'PUT', 'PATCH' => "Updated {$routeName}",
            'DELETE' => "Deleted {$routeName}",
            default => "{$method} {$routeName}",
        };
    }

    protected function sanitizeInput(array $data): array
    {
        return collect($data)
            ->map(function ($value, $key) {

                if (
                    Str::contains(
                        Str::lower((string) $key),
                        [
                            'password',
                            'token',
                            'secret',
                            'otp',
                        ]
                    )
                ) {
                    return '********';
                }

                /*
                 * Uploaded file ka actual content log nahi karna.
                 */
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    return [
                        'file_name' => $value->getClientOriginalName(),
                        'file_size' => $value->getSize(),
                        'mime_type' => $value->getMimeType(),
                    ];
                }

                if (is_array($value)) {
                    return $this->sanitizeInput($value);
                }

                /*
                 * Bahut bada text database me store na ho.
                 */
                if (is_string($value) && strlen($value) > 1000) {
                    return substr($value, 0, 1000).'...';
                }

                return $value;
            })
            ->toArray();
    }
}
