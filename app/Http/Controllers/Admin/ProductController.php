<?php

namespace App\Http\Controllers\Admin;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductBrandResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductBrand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Paginate product
     *
     * @return AnonymousResourceCollection
     */
    public function paginate()
    {
        $records = paginate(Product::latest());

        return ProductResource::collection($records);
    }

    /**
     * Create product
     *
     * @param Request $request
     * @throws ValidationException
     */
    public function create(Request $request)
    {
        $validated = $this->validateRequest($request);

        Product::create($validated);
    }

    /**
     * Update product
     *
     * @param Request $request
     * @param Product $product
     * @throws ValidationException
     */
    public function update(Request $request, Product $product)
    {
        $validated = $this->validateRequest($request);

        $product->update($validated);
    }

    /**
     * Upload thumbnail
     *
     * @param Request $request
     * @param Product $product
     * @throws ValidationException
     */
    public function uploadThumbnail(Request $request, Product $product)
    {
        $this->validate($request, [
            'file' => 'required|mimetypes:image/png,image/jpeg|file|max:300',
        ]);

        $file = $request->file('file');
        $thumbnail = savePublicFile($file, $product->path(), "thumbnail.{$file->extension()}");

        $product->update(['thumbnail' => $thumbnail]);
    }

    /**
     * Product already has a buyer
     *
     * @param Product $product
     * @return mixed|void
     */
    public function delete(Product $product)
    {
        $product->delete();
    }

    /**
     * Filter query by buyer
     *
     * @param Builder $query
     * @param Request $request
     */
    protected function filterByBuyer(Builder $query, Request $request)
    {
        if ($search = $request->get('searchUser')) {
            $query->whereHas('buyer', function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Validate request
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    protected function validateRequest(Request $request)
    {
        return $this->validate($request, [
            'title'       => ['required', 'string', 'max:250'],
            'label'       => ['required', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:10000'],
            'instruction' => ['required', 'string', 'max:10000'],
            'value'       => ['required', 'numeric', 'gt:0'],
            'currency'    => ['required', 'exists:supported_currencies,code'],
            'brand_id'    => ['required', 'exists:product_brands,id'],
        ]);
    }

    /**
     * Get brands
     *
     * @return AnonymousResourceCollection
     */
    public function getBrands()
    {
        return ProductBrandResource::collection($this->productBrands());
    }

    /**
     * Paginate brand records
     *
     * @return AnonymousResourceCollection
     */
    public function brandPaginate()
    {
        $records = paginate(ProductBrand::latest()->withCount('products'));

        return ProductBrandResource::collection($records);
    }

    /**
     * Create Product Brand
     *
     * @param Request $request
     * @throws ValidationException
     */
    public function createBrand(Request $request)
    {
        $validated = $this->validate($request, [
            'name'        => ['required', 'string', 'max:250', 'unique:product_brands'],
            'description' => ['required', 'string', 'max:10000'],
        ]);

        ProductBrand::create($validated);
    }

    /**
     * Update brand
     *
     * @param Request $request
     * @param ProductBrand $brand
     * @throws ValidationException
     */
    public function updateBrand(Request $request, ProductBrand $brand)
    {
        $validated = $this->validate($request, [
            'name'        => ['required', 'string', 'max:250', Rule::unique('product_brands')->ignore($brand)],
            'description' => ['required', 'string', 'max:10000'],
        ]);

        $brand->update($validated);
    }

    /**
     * Delete brand
     *
     * @param ProductBrand $brand
     * @return mixed|void
     */
    public function deleteBrand(ProductBrand $brand)
    {
        $brand->delete();
    }

    /**
     * All productBrands
     *
     * @return ProductBrand[]|Collection
     */
    protected function productBrands()
    {
        return ProductBrand::all();
    }
}
