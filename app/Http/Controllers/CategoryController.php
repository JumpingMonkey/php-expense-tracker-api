<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Create a new CategoryController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Middleware will be applied in the routes
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category,
        ]);
    }

    /**
     * Display the specified resource.
     * 
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        // Check if the category is being used by any expenses
        if ($category->expenses()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete category as it is being used by expenses',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }
}
