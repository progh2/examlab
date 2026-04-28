<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && !$user->current_tenant_id) {
            $firstTenantId = $user->tenants()->orderBy('tenants.id')->value('tenants.id');
            if ($firstTenantId) {
                $user->forceFill(['current_tenant_id' => $firstTenantId])->save();
            }
        }

        return $next($request);
    }
}

