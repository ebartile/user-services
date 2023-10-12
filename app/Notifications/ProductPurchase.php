<?php

namespace App\Notifications;

use Akaunting\Money\Money;
use App\Models\Product;
use App\Notifications\Traits\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProductPurchase extends Notification implements ShouldQueue
{
    use Queueable, Notifier;

    /**
     * @var Collection
     */
    public $items;

    /**
     * @var string
     */
    public $total;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Collection $items, Money $total)
    {
        $this->total = $total->format();

        $this->items = $items->map(function ($item) {
            $collection = collect([
                'quantity' => Arr::get($item, 'quantity'),
                'cost'     => null,
                'product' => null,
            ]);

            $product = Arr::get($item, 'product');
            $cost = Arr::get($item, 'cost');

            if ($product instanceof Product) {
                $collection->put('product', $product->title);
            }

            if ($cost instanceof Money) {
                $collection->put('cost', $cost->format());
            }

            return $collection->toArray();
        });
    }

    /**
     * Replacement parameters and Values
     *
     * @param $notifiable
     * @return array
     */
    protected function parameters($notifiable)
    {
        return [
            'total'       => $this->total,
            'items_count' => $this->items->count(),
        ];
    }
}
