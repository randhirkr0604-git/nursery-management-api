<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

class OccupantService
{
    public function getOccupants(
        Tenant $tenant,
        int $roomId
    ): Collection {

        // Verify room belongs to current tenant
        $room = $tenant->rooms()
            ->whereKey($roomId)
            ->firstOrFail();

        // Get children currently inside the room
        return $room->checkIns()
            ->with('child')
            ->whereNull('checked_out_at')
            ->orderBy('checked_in_at')
            ->get();
    }
}