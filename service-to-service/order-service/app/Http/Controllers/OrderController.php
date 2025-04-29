<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();
        return new OrderResource($orders, 'Success', 'List of orders');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'product_uuid' => 'required',
            'user_uuid' => 'required',
            'status' => 'required',
            'total_price' => 'required',
            'quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return new OrderResource(null, 'Failed', $validator->errors());
        }

        // 1. Insert Data Order
        $data = $request->all();
        $data['uuid'] = (string) Str::uuid();
        $product = Order::create($data);

        // 2. Update Stock ke Product Service
        Http::post('http://127.0.0.1:8002/api/products/'.$request->product_uuid.'/update-stock', [
            'product_quantity' => $request->quantity,
        ]);

        return new OrderResource($product, 'Success', 'Order created successfully');
    }

    public function show($uuid)
    {
        $order = Order::where('uuid', $uuid)->first();
        if ($order) {
            $data = $order->toArray();

            // Get the product details (consume)
            $productResponse = Http::
                timeout(5) // timeout
                ->get('http://127.0.0.1:8002/api/products/'.$order->product_uuid);
            $data['product'] = $productResponse->json()['data'];

            // Get the user details (consume)
            $userResponse = Http::get('http://127.0.0.1:8003/api/users/'.$order->user_uuid);
            $data['user'] = $userResponse->json()['data'];

            return new OrderResource($data, 'Success', 'Order found');
        } else {
            return new OrderResource(null, 'Failed', 'Order not found');
        }
    }
}
