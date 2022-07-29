<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OrderPayment
 *
 * @property string $stripe_id
 * @property int $order_id
 * @property float $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Order $order
 *
 * @package App\Models
 */
class OrderPayment extends Model
{
	protected $table = 'order_payments';
	protected $primaryKey = 'stripe_id';
	public $incrementing = false;

	protected $casts = [
		'order_id' => 'int',
		'amount' => 'float'
	];

	protected $fillable = [
        'stripe_id',
		'order_id',
		'amount'
	];

	public function order(): BelongsTo
    {
		return $this->belongsTo(Order::class);
	}
}
