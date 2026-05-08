@extends('layouts.app')

@section('title', '分类管理')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:flex-end;">
            <div>
                <h1 style="margin:0;">分类管理</h1>
                <p class="muted" style="margin:6px 0 0;">用于学员端"练习大类"选择。</p>
            </div>
            <button class="btn btn-primary" onclick="openAjaxModal('{{ route('admin.categories.create') }}', '新建分类')">新建分类</button>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>名称</th>
                        <th>Slug</th>
                        <th>排序</th>
                        <th>启用</th>
                        <th style="text-align:right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td class="muted">{{ $category->slug }}</td>
                            <td>{{ $category->sort_order }}</td>
                            <td>{{ $category->is_active ? '是' : '否' }}</td>
                            <td style="text-align:right;">
                                <button class="btn" onclick="openAjaxModal('{{ route('admin.categories.edit', $category) }}', '编辑分类')">编辑</button>
                                <button class="btn btn-danger" onclick="deleteCategory({{ $category->id }})">删除</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $categories->links() }}</div>
    </div>

    <div id="ajax-modal" class="modal">
        <div class="modal-backdrop" onclick="closeAjaxModal()"></div>
        <div class="modal-content">
            <div class="modal-header"><h3 id="ajax-modal-title"></h3><button class="modal-close" onclick="closeAjaxModal()">&times;</button></div>
            <div class="modal-body" id="ajax-modal-body"></div>
        </div>
    </div>
@endsection