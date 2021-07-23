<?php

namespace App\Http\Controllers;

use App\Models\BoxContent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class BoxContentController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createBoxContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'subscription_id' => 'required|integer',
                'box_id' => 'required|integer'
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $boxContent = BoxContent::create($validator->validate());

            if ($boxContent) {
                return ResponseHelper::successResponse($boxContent);
            } else {
                return ResponseHelper::errorResponse('cannot create box content', 500);
            }
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getBoxContent($subscription_id): JsonResponse
    {
        try {
            return ResponseHelper::successResponse(BoxContent::where('subscription_id', $subscription_id)->get());
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('box content not found', 404);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteBoxContent($id): JsonResponse
    {
        try {
            $address = BoxContent::findOrFail($id);
            return $address->delete() ?
                ResponseHelper::successResponse('box content deleted successfully') :
                ResponseHelper::errorResponse('box content cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('box content not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateBoxContent(Request $request, $id)
    {
        $validator = Validator::make($request->all(),
            [
                'subscription_id' => 'integer',
                'box_id' => 'integer'
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        try {
            $address = BoxContent::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $address->$key = $value;
            }
            return $address->save() ?
                ResponseHelper::successResponse('box content updated successfully') :
                ResponseHelper::errorResponse('box content cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('box content not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }

    }
}
