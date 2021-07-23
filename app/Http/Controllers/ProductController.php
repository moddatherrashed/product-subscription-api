<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class ProductController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|string',
                'description' => 'required|string',
                'long_description' => 'required|string',
                'content' => 'required|string',
                'image' => 'required|regex:/data:image\/([a-zA-Z]*);base64,([^\"]*)/u',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            //save image
            $saveImageResult = $this->saveImage($request->image);
            if ($saveImageResult['imageSaved']) {
                $product = Product::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'content' => $request->{'content'},
                    'long_description' => $request->long_description,
                    'image' => $saveImageResult['imagesFolderPath'] . $saveImageResult['imageName']
                ]);

                if ($product) {
                    return ResponseHelper::successResponse($product);
                } else {
                    return ResponseHelper::errorResponse('cannot create product', 500);
                }
            }

        } catch (QueryException | ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getProducts(): JsonResponse
    {
        try {
            return ResponseHelper::successResponse(Product::select('*')->get());
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('no products were found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteProduct($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            return $product->delete() && File::delete(storage_path() . $product->image) ?
                ResponseHelper::successResponse('product deleted successfully') :
                ResponseHelper::errorResponse('product cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('product not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateProduct(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(),
                [
                    'name' => 'string',
                    'description' => 'string',
                    'long_description' => 'string',
                    'image' => 'regex:/data:image\/([a-zA-Z]*);base64,([^\"]*)/u',
                ]);

            if ($validator->fails()) {
                return ResponseHelper::errorResponse($validator->errors(), 400);
            }
            $product = Product::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                if ($key === 'image') {
                    $saveImageResult = $this->saveImage($value);
                    // delete the old image if the image updated
                    if ($saveImageResult['imageSaved']) {
                        File::delete(storage_path() . $product->image);
                    }
                    $product->$key = $saveImageResult['imagesFolderPath'] . $saveImageResult['imageName'];
                } else {
                    $product->$key = $value;
                }
            }
            return $product->save() ?
                ResponseHelper::successResponse('product updated successfully') :
                ResponseHelper::errorResponse('product cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('product not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }

    }

    /**
     * @param $base64Image
     * @return array
     */
    public function saveImage($base64Image): array
    {
        $image = $base64Image;
        $img = preg_replace('/^data:image\/\w+;base64,/', '', $image);
        $img = str_replace(' ', '+', $img);
        $type = explode(';', $image)[0];
        $type = explode('/', $type)[1]; // png or jpg etc
        $imageName = strtolower(Str::random(20) . '.' . $type);
        $imagesFolderPath = '/app/images/products/';
        $imageSaved = File::put(app()->basePath('public') . $imagesFolderPath . $imageName, base64_decode($img));
        return [
            'imageSaved' => $imageSaved,
            'imagesFolderPath' => $imagesFolderPath,
            'imageName' => $imageName
        ];
    }
}
