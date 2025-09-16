<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (auth()->check()) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Log the request details
     */
    private function logRequest(Request $request, Response $response): void
    {
        $method = $request->method();
        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        $path = $request->path();

        // Skip logging for certain routes
        if ($this->shouldSkipLogging($path, $routeName)) {
            return;
        }

        // Determine action based on route and method
        $action = $this->determineAction($method, $path, $routeName);

        // Get additional details
        $details = [
            'status_code' => $response->getStatusCode(),
            'response_size' => strlen($response->getContent()),
        ];

        // Add request data for POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $details['request_data'] = $this->sanitizeRequestData($request->all());
        }

        // Log the activity
        AuditService::log($action, $details);
    }

    /**
     * Determine if we should skip logging for this request
     */
    private function shouldSkipLogging(string $path, ?string $routeName): bool
    {
        // Skip logging for these paths
        $skipPaths = [
            'api/dashboard/statistics',
            'api/dashboard/health',
            'api/audit-logs', // Prevent infinite logging
        ];

        // Skip logging for these route names
        $skipRoutes = [
            'audit-logs.index',
            'audit-logs.show',
        ];

        if (in_array($path, $skipPaths) || in_array($routeName, $skipRoutes)) {
            return true;
        }

        // Skip logging for GET requests to certain paths
        if (str_starts_with($path, 'api/') && request()->isMethod('GET')) {
            $skipGetPaths = [
                'api/students',
                'api/employees',
                'api/users',
            ];

            foreach ($skipGetPaths as $skipPath) {
                if (str_starts_with($path, $skipPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine the action description
     */
    private function determineAction(string $method, string $path, ?string $routeName): string
    {
        // Use route name if available
        if ($routeName) {
            return $this->formatRouteName($routeName);
        }

        // Fallback to path-based action
        return $this->formatPathAction($method, $path);
    }

    /**
     * Format route name to readable action
     */
    private function formatRouteName(string $routeName): string
    {
        $routeName = str_replace(['api.', '.'], ['', ' '], $routeName);
        return ucwords($routeName);
    }

    /**
     * Format path to readable action
     */
    private function formatPathAction(string $method, string $path): string
    {
        $path = str_replace('api/', '', $path);
        $segments = explode('/', $path);
        
        $resource = $segments[0] ?? 'unknown';
        $action = $this->getMethodAction($method);

        return "{$action} {$resource}";
    }

    /**
     * Get action based on HTTP method
     */
    private function getMethodAction(string $method): string
    {
        return match ($method) {
            'GET' => 'View',
            'POST' => 'Create',
            'PUT', 'PATCH' => 'Update',
            'DELETE' => 'Delete',
            default => 'Access',
        };
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}