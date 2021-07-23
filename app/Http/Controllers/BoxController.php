<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class BoxController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createBox(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'title' => 'required|string',
                'price' => 'required|numeric',
                'size' => 'required|integer',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $box = Box::create($validator->validate());

            if ($box) {
                return ResponseHelper::successResponse($box);
            } else {
                return ResponseHelper::errorResponse('cannot create box', 500);
            }
        } catch (QueryException | ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getBoxes(): JsonResponse
    {
        try {
            return ResponseHelper::successResponse(Box::select('*')->get());
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('no boxes were found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteBox($id): JsonResponse
    {
        try {
            $box = Box::findOrFail($id);
            return $box->delete() ?
                ResponseHelper::successResponse('box deleted successfully') :
                ResponseHelper::errorResponse('box cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('box not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateBox(Request $request, $id)
    {
        try {
            $box = Box::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $box->$key = $value;
            }
            return $box->save() ?
                ResponseHelper::successResponse('box updated successfully') :
                ResponseHelper::errorResponse('box cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('box not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }

    }
}
