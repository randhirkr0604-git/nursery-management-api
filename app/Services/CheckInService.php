<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\CheckIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInService
{
    public function checkIn(
        Tenant $tenant,
        int $roomId,
        int $childId
    ): CheckIn {

        return DB::transaction(function () use (
            $tenant,
            $roomId,
            $childId
        ) {

            // Verify room belongs to current tenant
            $room = $tenant->rooms()
                ->whereKey($roomId)
                ->firstOrFail();
            

            // Verify child belongs to current tenant
            // Lock child row to prevent concurrent check-ins
            $child = $tenant->children()
                ->whereKey($childId)
                ->lockForUpdate()
                ->firstOrFail();

            // Prevent child from being checked into multiple rooms
            $activeCheckIn = $tenant->checkIns()
                ->where('child_id', $child->id)
                ->whereNull('checked_out_at')
                ->exists();

            if ($activeCheckIn) {
                throw ValidationException::withMessages([
                    'child_id' => [
                        'This child is already checked into a room.'
                    ]
                ]);
            }

            // Create check-in
            $checkIn = $tenant->checkIns()->create([
                'room_id' => $room->id,
                'child_id' => $child->id,
                'checked_in_at' => now(),
            ]);

            return $checkIn->load(['child', 'room']);
        });
    }
}