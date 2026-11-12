<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        // Reject missing tenant header
        if (!$tenantId) {
            return response()->json([
                'message' => 'X-Tenant-ID header is required.'
            ], 400);
        }

        // Find tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'message' => 'Invalid tenant.'
            ], 404);
        }

        // Attach tenant to current request
        $request->attributes->set('tenant', $tenant);
        
        return $next($request);
    }
}
