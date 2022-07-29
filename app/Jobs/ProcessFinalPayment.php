<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Notifications\OrderFullyPaid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stripe\Charge;
use Stripe\Refund;

class ProcessFinalPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Customer
     */
    private $customer;
    /**
     * @var Order
     */
    private $order;

    /**
     * Create a new job instance.
     *
     * @param Customer $customer
     * @param Order $order
     */
    public function __construct(Customer $customer,Order $order)
    {
        //
        $this->customer = $customer;
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $charge = Charge::create ([
            "amount" => ($this->order->amount/2)*100,
            "currency" => "USD",
            "customer" => $this->customer->stripe_id,
            "description" => "Final payment of {$this->order->name}"
        ]);

        if($charge->status == "failed"){
            $this->refund($this->order);
            return;
        }

        $this->order->order_payments()->create([
            'stripe_id'=>$charge->id,
            'amount'=> $charge->amount/100
        ]);

        $this->order->update(["status"=>"full_paid"]);

        $this->order->notify(new OrderFullyPaid($this->order));

    }

    private function refund(Order $order){
        $order->load(["order_payments"]);
        $order->order_payments->each(function($payment){
            Refund::create([
                'charge' => $payment->stripe_id,
            ]);
        });
        $order->update(["status"=>"refunded"]);
    }
}
