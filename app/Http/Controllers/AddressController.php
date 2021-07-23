<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class AddressController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'firstname' => 'required',
                'lastname' => 'required',
                'street' => 'required',
                'zip' => 'required',
                'city' => 'required',
                'country' => 'required',
                'note' => 'string',
                'user_id' => 'required',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $address = Address::create($validator->validate());

            if ($address) {
                return ResponseHelper::successResponse($address);
            } else {
                return ResponseHelper::errorResponse('cannot create address', 500);
            }
        } catch (QueryException | ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getUserAddress(): JsonResponse
    {
        $userId = auth()->user()->id;
        try {
            return ResponseHelper::successResponse(Address::where('user_id', $userId)->get());
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('address not found', 404);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteAddress($id): JsonResponse
    {
        try {
            $address = Address::findOrFail($id);
            return $address->delete() ?
                ResponseHelper::successResponse('address deleted successfully') :
                ResponseHelper::errorResponse('address cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('address not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateAddress(Request $request, $id): JsonResponse
    {
        try {
            $address = Address::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $address->$key = $value;
            }
            return $address->save() ?
                ResponseHelper::successResponse('address updated successfully') :
                ResponseHelper::errorResponse('address cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('address not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }

    }
}
