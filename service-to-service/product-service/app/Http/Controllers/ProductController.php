<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return new ProductResource($products, 'Success', 'List of products');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'stock' => 'required',
        ]);

        if ($validator->fails()) {
            return new ProductResource(null, 'Failed', $validator->errors());
        }

        $data = $request->all();
        $data['uuid'] = (string) Str::uuid();
        $product = Product::create($data);

        return new ProductResource($product, 'Success', 'Product created successfully');
    }

    public function show($uuid)
    {
        $product = Product::where('uuid', $uuid)->first();
        if ($product) {
            return new ProductResource($product, 'Success', 'Product found');
        } else {
            return new ProductResource(null, 'Failed', 'Product not found');
        }
    }

    public function update(Request $request, $uuid)
    {
        $product = Product::where('uuid', $uuid)->first();
        if ($product) {
            $data = $request->all();
            $data['uuid'] = $uuid;
            $product->update($data);

            return new ProductResource($product, 'Success', 'Product updated successfully');
        } else {
            return new ProductResource(null, 'Failed', 'Product not found');
        }
    }

    public function destroy($uuid)
    {
        $product = Product::where('uuid', $uuid);
        if ($product) {
            $product->delete();
            return new ProductResource($product, 'Success', 'Product deleted successfully');
        } else {
            return new ProductResource(null, 'Failed', 'Product not found');
        }
    }
}
