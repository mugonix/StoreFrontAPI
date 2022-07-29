<?php

namespace App\Http\Controllers;

use App\Helpers\UploadAble;
use App\Models\Product;
use App\Models\SellerInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    use UploadAble;

    public function index(): JsonResponse
    {
        $products = Product::orderBy("created_at","DESC")->paginate(\request("size",12));

        return response()->json($products);
    }

    public function userIndex(): JsonResponse
    {
        $products = Product::whereOwnerId(auth()->id())->orderBy("created_at","DESC")->paginate(\request("size",50));

        return response()->json($products);
    }

    public function store(): JsonResponse
    {
        $validator = Validator::make(\request()->all(), [
            "name" => 'required',
            "price" => 'required|numeric',
            "image" => "required|image|dimensions:ratio=1",
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "error" => $validator->errors()->first()], 422);
        }

        $user_id = auth()->id();
        $path = $this->uploadOne(\request()->file("image"),"products/". $user_id);

        $product = Product::create([
            'owner_id' => $user_id,
            'name' => \request("name"),
            'price'=> \request("price"),
            'image_path'=> $path
        ]);

        return response()->json(["success" => true, "data"=>$product], 201);
    }

    public function update(Product $product): JsonResponse
    {
        if (!\Gate::allows('update-product', $product)) {
            return response()->json(["success" => false, "error" => "Unable to update product listing!"], 403);
        }

        $validator = Validator::make(\request()->all(), [
            "name" => 'required',
            "price" => 'required|numeric',
            "image" => "image|dimensions:ratio=1",
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "error" => $validator->errors()->first()], 422);
        }

        $user_id = auth()->id();
        $data = [
            'owner_id' => $user_id,
            'name' => \request("name"),
            'price'=> \request("price"),
        ];

        if(\request()->has("image")){
            $this->deleteOne($product->image_path);
            $path = $this->uploadOne(\request()->file("image"),"products/". $user_id);
            $data['image_path'] = $path;
        }

       $product->update($data);

        return response()->json(["success" => true, "data"=>$product]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if (!\Gate::allows('delete-product', $product)) {
            return response()->json(["success" => false, "error" => "Unable to delete product listing!"], 403);
        }

        $product->delete();

        return response()->json(["success" => true]);

    }


}
