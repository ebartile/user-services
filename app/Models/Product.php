<?php

namespace App\Models;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'stock',
        'formatted_value',
    ];

    /**
     * Get path for thumbnail
     *
     * @return string
     */
    public function path()
    {
        return "products/{$this->id}";
    }

    /**
     * Get logo url
     *
     * @param $value
     * @return string
     */
    public function getThumbnailAttribute($value)
    {
        return $value ? url($value) : $value;
    }

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['brand'];

    /**
     * Value Object
     *
     * @return Money
     */
    public function getValueObject()
    {
        return new Money($this->getAttributeFromArray('value'), new Currency($this->currency), true);
    }

    /**
     * Value
     *
     * @return float
     */
    public function getValueAttribute()
    {
        return $this->getValueObject()->getValue();
    }

    /**
     * Formatted Value
     *
     * @return string
     */
    public function getFormattedValueAttribute()
    {
        return $this->getValueObject()->format();
    }

    /**
     * Get price in another currency
     *
     * @param User|null $user
     * @return Money
     */
    public function getPrice($user)
    {
        $currency = new Currency($user?->currency ?: defaultCurrency());

        return $this->getValueObject();
    }

    /**
     * Get related brand
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id', 'id');
    }

}
