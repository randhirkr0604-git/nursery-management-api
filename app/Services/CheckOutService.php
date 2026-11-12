<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\CheckIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckOutService
{
    public function checkOut(
        Tenant $tenant,
        int $checkInId
    ): CheckIn {

        return DB::transaction(function () use (
            $tenant,
            $checkInId
        ) {

            // Find check-in belonging to current tenant
            $checkIn = $tenant->checkIns()
                ->whereKey($checkInId)
                ->lockForUpdate()
                ->firstOrFail();

            // Prevent duplicate checkout
            if ($checkIn->checked_out_at !== null) {
                throw ValidationException::withMessages([
                    'check_in_id' => [
                        'This child has already been checked out.'
                    ]
                ]);
            }

            // Update checkout timestamp
            $checkIn->update([
                'checked_out_at' => now(),
            ]);

            return $checkIn->load(['child', 'room']);
        });
    }
}