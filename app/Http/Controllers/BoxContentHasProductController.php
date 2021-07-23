<?php

namespace App\Http\Controllers;

use App\Models\BoxContentHasProduct;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class BoxContentHasProductController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'box_content_id' => 'required|integer',
                'product_id' => 'required|integer',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $boxContentHasProduct = BoxContentHasProduct::create($validator->validate());

            if ($boxContentHasProduct) {
                return ResponseHelper::successResponse($boxContentHasProduct);
            } else {
                return ResponseHelper::errorResponse('cannot create relation between box content and product', 500);
            }
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'box_content_id' => 'required|integer',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        try {
            return ResponseHelper::successResponse(BoxContentHasProduct::where('box_content_id', $request->box_content_id)->get());
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('relation between box content and product was not found', 404);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            $boxContentHasProduct = BoxContentHasProduct::findOrFail($id);
            return $boxContentHasProduct->delete() ?
                ResponseHelper::successResponse('relation between box content and product deleted successfully') :
                ResponseHelper::errorResponse('relation between box content and product cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('relation between box content and product not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $boxContentHasProduct = BoxContentHasProduct::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $boxContentHasProduct->$key = $value;
            }
            return $boxContentHasProduct->save() ?
                ResponseHelper::successResponse('relation between box content and product updated successfully') :
                ResponseHelper::errorResponse('relation between box content and product cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('relation between box content and product not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }

    }
}
