<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::with('categories')->paginate(10);
            return ResponseHelper::success(
                __('product.retrieved'),
                ProductResource::collection($products),
                200,
                [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Product index failed: ' . $e->getMessage());
            return ResponseHelper::error(__('product.error'), 500, $e->getMessage());
        }
    }

    public function store(ProductRequest $request)
{
    try {
        $validated = $request->validated();
        Log::info('Validated data:', $validated);
        $images = [];
        if ($request->hasFile('images') && is_array($request->file('images'))) {
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $images[] = $image->store('products', 'public');
                    Log::info('Image stored: ' . $images[count($images) - 1]);
                } else {
                    Log::warning('Invalid image detected');
                }
            }
        } else {
            Log::info('No images uploaded or invalid images format');
        }

        $product = Product::create([
            'name' => [
                'en' => $validated['name']['en'],
                'ar' => $validated['name']['ar'],
            ],
            'description' => [
                'en' => $validated['description']['en'] ?? null,
                'ar' => $validated['description']['ar'] ?? null,
            ],
            'images' => $images ? json_encode($images) : null,
            'price' => $validated['price'],
            'discounted_price' => $validated['discounted_price'] ?? null,
            'quantity' => $validated['quantity'],
            'status' => [
                'en' => $validated['status']['en'],
                'ar' => $validated['status']['ar'],
            ],
        ]);

        if (!empty($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        return ResponseHelper::success(
            __('product.created'),
            new ProductResource($product->load('categories')),
            201
        );
    } catch (\Exception $e) {
        Log::error('Product store failed: ' . $e->getMessage());
        return ResponseHelper::error(__('product.error'), 500, $e->getMessage());
    }
}

    public function show($id)
    {
        try {
            $product = Product::with('categories')->findOrFail($id);
            return ResponseHelper::success(
                __('product.retrieved'),
                new ProductResource($product),
                200
            );
        } catch (\Exception $e) {
            Log::error('Product show failed: ' . $e->getMessage());
            return ResponseHelper::error(__('product.not_found'), 404, $e->getMessage());
        }
    }

    public function update(ProductRequest $request, $id)
    {
        Log::info('Update request data:', $request->all());
        Log::info('Update request files:', $request->files->all());
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validated();
            $images = json_decode($product->images, true) ?? [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image->isValid()) {
                        $images[] = $image->store('products', 'public');
                    }
                }
            }

            $product->update([
                'name' => [
                    'en' => $validated['name']['en'],
                    'ar' => $validated['name']['ar'],
                ],
                'description' => [
                    'en' => $validated['description']['en'] ?? null,
                    'ar' => $validated['description']['ar'] ?? null,
                ],
                'images' => $images ? json_encode($images) : null,
                'price' => $validated['price'],
                'discounted_price' => $validated['discounted_price'] ?? null,
                'quantity' => $validated['quantity'],
                'status' => [
                    'en' => $validated['status']['en'],
                    'ar' => $validated['status']['ar'],
                ],
            ]);

            if (isset($validated['category_ids'])) {
                $product->categories()->sync($validated['category_ids']);
            }

            return ResponseHelper::success(
                __('product.updated'),
                new ProductResource($product->load('categories')),
                200
            );
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return ResponseHelper::error(__('product.update_failed'), 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->categories()->detach();
            $product->delete();
            return ResponseHelper::success(__('product.deleted'), null, 200);
        } catch (\Exception $e) {
            Log::error('Product delete failed: ' . $e->getMessage());
            return ResponseHelper::error(__('product.delete_failed'), 500, $e->getMessage());
        }
    }
}