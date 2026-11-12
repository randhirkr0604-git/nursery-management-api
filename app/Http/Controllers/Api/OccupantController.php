<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\OccupantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccupantController extends Controller
{
    public function index(
        Request $request,
        int $room,
        OccupantService $occupantService
    ): JsonResponse {

        $tenant = $request->attributes->get('tenant');

        $occupants = $occupantService->getOccupants(
            $tenant,
            $room
        );

        return response()->json([
            'message' => 'Current room occupants retrieved successfully.',
            'data' => $occupants->map(function ($checkIn) {
                return [
                    'check_in_id' => $checkIn->id,
                    'child_id' => $checkIn->child_id,
                    'child_name' => $checkIn->child->name,
                    'room_id' => $checkIn->room_id,
                    'checked_in_at' => $checkIn->checked_in_at,
                ];
            })->values(),
            'total_occupants' => $occupants->count(),
        ]);
    }
}