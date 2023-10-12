<?php

namespace App\Http\Controllers;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Exceptions\TransferException;
use App\Http\Requests\VerifiedRequest;
use App\Http\Resources\ProductBrandResource;
use App\Http\Resources\ProductResource;
use App\Models\FeatureLimit;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\PaymentAccount;
use App\Models\User;
use App\Notifications\ProductPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Get product records
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws ValidationException
     */
    public function get(Request $request)
    {
        $validated = $this->validate($request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|min:1',
        ]);

        $records = Product::whereIn('id', $validated['ids'])->get();

        return ProductResource::collection($records);
    }

    /**
     * Purchase products
     *
     * @param VerifiedRequest $request
     * @throws ValidationException
     */
    public function purchase(VerifiedRequest $request)
    {
        $validated = $this->validate($request, [
            'items'            => 'required|array|min:1',
            'items.*'          => 'required|array:id,quantity',
            'items.*.id'       => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $items = collect($validated['items']);

        Auth::user()->acquireLock(function (User $user) use ($items) {
            $items->transform(function ($item) use ($user) {
                $product = Product::find($item['id']);
                $price = $product->getPrice($user);

                $quantity = $item['quantity'];

                $cost = $price->multiply($quantity);

                return compact('product', 'cost', 'quantity');
            });

            $total = $items->reduce(function (Money $total, $item) {
                return $total->add($item['cost']);
            }, new Money(0, new Currency("USD"), True));

            $user->notify(new ProductPurchase($items, $total));            
        });
    }


    /**
     * Get purchase description
     *
     * @param Money $total
     * @return string
     */
    protected function getDescription(Money $total)
    {
        return "Product purchase ({$total->format()})";
    }

    /**
     * Get brands
     *
     * @return AnonymousResourceCollection
     */
    public function getBrands()
    {
        return ProductBrandResource::collection(ProductBrand::all());
    }

    /**
     * Paginate products
     *
     * @return AnonymousResourceCollection
     */
    public function paginate()
    {
        $records = paginate(Product::query());

        return ProductResource::collection($records);
    }
}
