<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('id', 'desc')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        Category::create($validated);

        return response()->json(['message' => 'Kategori berhasil ditambahkan'], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $category->update($validated);

        return response()->json(['message' => 'Kategori berhasil diperbarui']);
    }

    public function destroy($id)
    {
        Category::destroy($id);
        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}
