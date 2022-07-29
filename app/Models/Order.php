<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

/**
 * Class Order
 *
 * @property int $id
 * @property int $product_id
 * @property string $customer_name
 * @property string $order_number
 * @property string $email
 * @property string $name
 * @property float $amount
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Product $product
 * @property Customer $customer
 * @property Collection|OrderPayment[] $order_payments
 *
 * @package App\Models
 */
class Order extends Model
{
    use Notifiable;
	protected $table = 'orders';

	protected $casts = [
		'product_id' => 'int',
		'amount' => 'float'
	];

	protected $fillable = [
		'product_id',
		'customer_name',
        'order_number',
		'email',
		'name',
		'amount',
		'status'
	];

    public function getStatusAttribute($value)
    {
        return ucwords(str_replace("_"," ",$value));
    }

	public function product(): BelongsTo
    {
		return $this->belongsTo(Product::class);
	}

    public function customer(): BelongsTo
    {
		return $this->belongsTo(Customer::class);
	}

	public function order_payments(): HasMany
    {
		return $this->hasMany(OrderPayment::class);
	}

    /**
     * Route notifications for the mail channel.
     *
     * @param  Notification  $notification
     * @return array
     */
    public function routeNotificationForMail($notification): array
    {
        return [$this->email => $this->customer_name];
    }

    public static function generateOrderNumber(): string
    {
        return "ORD-" . strtoupper(uniqid());
    }
}
