<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFinalPayment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderFullyPaid;
use App\Notifications\OrderPartlyPaid;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;

class OrderController extends Controller
{

    public function viewMyOrders(){
        $orders = Order::whereHas("product",function($q){
            return $q->whereOwnerId(auth()->id());
        })
            ->orderBy("created_at","DESC")->paginate(\request("size",50));

        return response()->json($orders);
    }

    public function placeOrder(): JsonResponse
    {
        $validator = Validator::make(\request()->all(), [
            "product_id" => 'required|exists:products,id',
            "stripeToken" => 'required|string',
            "name" => "required",
            "email" => "required|email",
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "error" => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        try{
            DB::beginTransaction();
            $product = Product::findOrFail($data["product_id"]);

            $customer = Customer::getOrCreateCustomer($data["email"],$data["stripeToken"]);

            $charge = Charge::create ([
                "amount" => $product->price*100,
                "currency" => "USD",
                "customer" => $customer->stripe_id,
                "description" => "Purchase of $product->name"
            ]);

            $order = $customer->orders()->create([
                'product_id' => $product->id,
                'order_number' => Order::generateOrderNumber(),
                'customer_name'=>$data["name"],
                'email'=>$data["email"],
                'name' => $product->name,
                'amount' => $product->price,
                'status'=> 'full_paid'
            ]);

            $order->order_payments()->create([
                'stripe_id'=>$charge->id,
                'amount'=> $charge->amount/100
            ]);

            DB::commit();
            $order->notify(new OrderFullyPaid($order));

            return response()->json(["success" => true, "message" => "Your order has been placed successfully!","data"=>$order], 201);
        }catch (ApiErrorException  $exception){
            DB::rollBack();
            return response()->json(["success" => false, "error" => $exception->getMessage()], 422);
        }
        catch(Exception $exception){
            DB::rollBack();
            report($exception);
            return response()->json(["success" => false, "error" => "Failed to process transaction, please try again"], 422);

        }
    }

    public function deferredPaymentOrder(): JsonResponse
    {
        $validator = Validator::make(\request()->all(), [
            "name" => "required",
            "email" => "required|email",
            "product_id" => 'required|exists:products,id',
            "stripeToken" => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "error" => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        try{
            DB::beginTransaction();
            $product = Product::findOrFail($data["product_id"]);

            $customer = Customer::getOrCreateCustomer($data["email"],$data["stripeToken"]);

            $charge = Charge::create ([
                "amount" => ($product->price/2)*100,
                "currency" => "USD",
                "customer" => $customer->stripe_id,
                "description" => "Part payment for $product->name"
            ]);


            if(!$charge->paid){
                DB::rollBack();
                return response()->json(["success" => false, "error" => "Failed to make payment with"], 422);
            }

            $order = $customer->orders()->create([
                'product_id' => $product->id,
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $data["name"],
                'email' => $data["email"],
                'name' => $product->name,
                'amount' => $product->price,
                'status' => 'part_paid'
            ]);

            $order->order_payments()->create([
                'stripe_id'=>$charge->id,
                'amount'=> $charge->amount/100
            ]);

            DB::commit();

            $order->notify(new OrderPartlyPaid($order));

            ProcessFinalPayment::dispatch($customer,$order)->delay(now()->addMinutes(5));

            return response()->json(["success" => true, "message" => "Your order has been placed successfully!", "data" => $order], 201);
        }catch (ApiErrorException  $exception){
            DB::rollBack();
            return response()->json(["success" => false, "error" => $exception->getMessage()], 422);
        }
        catch(Exception $exception){
            DB::rollBack();
            report($exception);
            return response()->json(["success" => false, "error" => "Failed to process transaction, please try again"], 422);

        }
    }

}
