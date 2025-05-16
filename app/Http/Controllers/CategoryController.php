<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::all();
            return ResponseHelper::success(__('category.retrieved'), CategoryResource::collection($categories), 200);
        } catch (\Exception $e) {
            return ResponseHelper::error(__('category.error'), 500, $e->getMessage());
        }
    }

    public function store(CategoryRequest $request)
{
    try {
        $validated = $request->validated();
        Log::info('Validated data:', $validated);
        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('categories', 'public');
            Log::info('Image stored at: ' . $imagePath);
        } else {
            Log::info('No valid image uploaded');
        }

        $category = Category::create([
            'name' => [
                'en' => $validated['name']['en'],
                'ar' => $validated['name']['ar'],
            ],
            'description' => [
                'en' => $validated['description']['en'] ?? null,
                'ar' => $validated['description']['ar'] ?? null,
            ],
            'image' => $imagePath,
            'status' => [
                'en' => $validated['status']['en'],
                'ar' => $validated['status']['ar'],
            ],
        ]);

        return ResponseHelper::success(__('category.created'), new CategoryResource($category), 201);
    } catch (\Exception $e) {
        Log::error('Category store failed: ' . $e->getMessage());
        return ResponseHelper::error(__('category.error'), 500, $e->getMessage());
    }
}

    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return ResponseHelper::success(__('category.retrieved'), new CategoryResource($category), 200);
        } catch (\Exception $e) {
            return ResponseHelper::error(__('category.not_found'), 404, $e->getMessage());
        }
    }

    public function update(CategoryRequest $request, $id)
    {
        Log::info('Update request data:', $request->all());
        Log::info('Update request files:', $request->files->all());
        try {
            $category = Category::findOrFail($id);
            $imagePath = $category->image;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imagePath = $request->file('image')->store('categories', 'public');
            }

            $category->update([
                'name' => [
                    'en' => $request->input('name.en'),
                    'ar' => $request->input('name.ar'),
                ],
                'description' => [
                    'en' => $request->input('description.en') ?? null,
                    'ar' => $request->input('description.ar') ?? null,
                ],
                'image' => $imagePath,
                'status' => [
                    'en' => $request->input('status.en'),
                    'ar' => $request->input('status.ar'),
                ],
            ]);

            return ResponseHelper::success(__('category.updated'), new CategoryResource($category), 200);
        } catch (\Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            return ResponseHelper::error(__('category.update_failed'), 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return ResponseHelper::error(__('category.not_found'), 404);
            }

            $category->delete();

            return ResponseHelper::success(__('category.deleted'), [], 200);
        } catch (\Exception $e) {
            return ResponseHelper::error(__('category.delete_failed'), 500, $e->getMessage());
        }
    }
}