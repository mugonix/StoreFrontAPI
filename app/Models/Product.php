<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 *
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property float $price
 * @property string $image_path
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user
 * @property Collection|Order[] $orders
 *
 * @package App\Models
 */
class Product extends Model
{
	use SoftDeletes, HasFactory;
	protected $table = 'products';

	protected $casts = [
		'owner_id' => 'int',
		'price' => 'float'
	];

	protected $fillable = [
		'owner_id',
		'name',
		'price',
		'image_path'
	];

    public function getImagePathAttribute($value): string
    {
        return asset("storage".DIRECTORY_SEPARATOR.$value);
    }

	public function user(): BelongsTo
    {
		return $this->belongsTo(User::class, 'owner_id');
	}

	public function orders(): HasMany
    {
		return $this->hasMany(Order::class);
	}
}
