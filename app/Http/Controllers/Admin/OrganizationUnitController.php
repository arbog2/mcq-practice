<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUnit;
use Illuminate\Http\Request;

class OrganizationUnitController extends Controller
{
    public function index()
    {
        $roots = OrganizationUnit::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.organization-units.index', compact('roots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:organization_units,id'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId) {
            $parent = OrganizationUnit::query()->findOrFail($parentId);
            abort_if($parent->parent_id !== null, 422, __('只能选择一级分类作为父级。'));
        }

        OrganizationUnit::create([
            'parent_id' => $parentId,
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.organization-units.index')->with('status', __('已保存。'));
    }

    public function destroy(OrganizationUnit $organizationUnit)
    {
        if ($organizationUnit->children()->exists()) {
            return redirect()->back()->withErrors(['delete' => __('请先删除其下属二级分类。')]);
        }

        if ($organizationUnit->users()->exists()) {
            return redirect()->back()->withErrors(['delete' => __('仍有用户归属该分类，无法删除。')]);
        }

        $organizationUnit->delete();

        return redirect()->route('admin.organization-units.index')->with('status', __('已删除。'));
    }
}
