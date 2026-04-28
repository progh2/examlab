<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    public static function currentTenantId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return $user->current_tenant_id;
    }

    public static function currentTenant(): ?Tenant
    {
        $id = self::currentTenantId();
        if (!$id) {
            return null;
        }

        return Tenant::query()->find($id);
    }
}

