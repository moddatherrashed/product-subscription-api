<?php

namespace App\Http\Controllers;

use App\Models\DeliveryInterval;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class DeliveryIntervalController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createDeliveryInterval(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'zip_code' => 'required',
                'city' => 'required'
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $deliveryInterval = DeliveryInterval::create($validator->validate());
            if ($deliveryInterval) {
                return ResponseHelper::successResponse($deliveryInterval);
            } else {
                return ResponseHelper::errorResponse('cannot create delivery interval', 500);
            }
        } catch (QueryException | ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    public function getAllIntervals(): JsonResponse
    {
        try {
            return ResponseHelper::successResponse(DeliveryInterval::select('*')->get());
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteInterval($id)
    {
        try {
            $interval = DeliveryInterval::findOrFail($id);
            return $interval->delete() ?
                ResponseHelper::successResponse('interval deleted successfully') :
                ResponseHelper::errorResponse('interval cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('interval not found', 404);
        }
    }

    public function updateInterval(Request $request, $id)
    {
        try {
            $interval = DeliveryInterval::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $interval->$key = $value;
            }
            return $interval->save() ?
                ResponseHelper::successResponse('interval updated successfully') :
                ResponseHelper::errorResponse('interval cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('interval not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }
}
