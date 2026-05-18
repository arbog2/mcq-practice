<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => null,
            'action' => route('admin.categories.store'),
            'method' => 'POST'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json(['message' => '分类已创建。', 'reload' => true]);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'action' => route('admin.categories.update', $category),
            'method' => 'PUT'
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json(['message' => '分类已更新。', 'reload' => true]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => '分类已删除。', 'reload' => true]);
    }

    public function toggleActive(Category $category)
    {
        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        return response()->json([
            'message' => '分类已' . ($category->is_active ? '启用' : '禁用') . '。',
            'is_active' => $category->is_active,
        ]);
    }
}