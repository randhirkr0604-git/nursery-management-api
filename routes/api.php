<?php 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\OccupantController;

Route::middleware('tenant')->get('/tenant-check', function (Request $request) {

    $tenant = $request->attributes->get('tenant');

    return response()->json([
        'message' => 'Tenant identified successfully.',
        'tenant' => [
            'id' => $tenant->id,
            'name' => $tenant->name,
        ],
    ]);
});


Route::middleware('tenant')->group(function () {


    //create check-ins
    Route::post(
        '/rooms/{room}/check-ins',
        [CheckInController::class, 'store']
    );

    //check out api
    Route::post(
        '/check-ins/{id}/check-out',
        [CheckInController::class, 'checkOut']
    );

    //get occupants room detail
    Route::get(
        '/rooms/{room}/occupants',
        [OccupantController::class, 'index']
    );

});


