<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckInRequest;
use App\Models\Tenant;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use App\Services\CheckOutService;
use Illuminate\Http\Request;


class CheckInController extends Controller
{
    public function store(
        StoreCheckInRequest $request,
        int $room,
        CheckInService $checkInService
    ): JsonResponse {

        $tenant = $request->attributes->get('tenant');

        $checkIn = $checkInService->checkIn(
            $tenant,
            $room,
            $request->integer('child_id')
        );

        return response()->json([
            'message' => 'Child checked into room successfully.',
            'data' => [
                'check_in_id' => $checkIn->id,
                'room_id' => $checkIn->room_id,
                'room_name' => $checkIn->room->name,
                'child_id' => $checkIn->child_id,
                'child_name' => $checkIn->child->name,
                'checked_in_at' => $checkIn->checked_in_at,
            ],
        ], 201);
    }

    public function checkOut(
    Request $request,
        int $id,
        CheckOutService $checkOutService
    ): JsonResponse {

        $tenant = $request->attributes->get('tenant');

        $checkIn = $checkOutService->checkOut(
            $tenant,
            $id
        );

        return response()->json([
            'message' => 'Child checked out successfully.',
            'data' => [
                'check_in_id' => $checkIn->id,
                'room_id' => $checkIn->room_id,
                'room_name' => $checkIn->room->name,
                'child_id' => $checkIn->child_id,
                'child_name' => $checkIn->child->name,
                'checked_in_at' => $checkIn->checked_in_at,
                'checked_out_at' => $checkIn->checked_out_at,
            ],
        ]);
    }
}