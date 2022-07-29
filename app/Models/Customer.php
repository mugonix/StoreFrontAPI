<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Customer
 *
 * @property int $id
 * @property string $email
 * @property string $stripe_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Customer extends Model
{
	protected $table = 'customers';

	protected $fillable = [
		'email',
		'stripe_id'
	];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public static function getOrCreateCustomer($email,$stripeToken){
        $customer = Customer::whereEmail($email)->first();
        if(is_null($customer)){
            $new_cust = \Stripe\Customer::create(["email"=>$email,"source"=>$stripeToken]);
            return Customer::create([
                "email"=>$email,
                "stripe_id"=>$new_cust->id
            ]);
        }
        return $customer;
    }
}
