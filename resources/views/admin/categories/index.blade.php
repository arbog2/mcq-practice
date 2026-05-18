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
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" class="toggle-active" data-id="{{ $category->id }}" {{ $category->is_active ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
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

<style>
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    cursor: pointer;
}
.toggle-switch input {
    display: none;
}
.toggle-slider {
    position: absolute;
    inset: 0;
    background: #ccc;
    border-radius: 24px;
    transition: .3s;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .3s;
}
.toggle-switch input:checked + .toggle-slider {
    background: #22c55e;
}
.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(20px);
}
</style>

<script>
document.querySelectorAll('.toggle-active').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch('/admin/categories/' + this.dataset.id + '/toggle-active', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
            },
            body: '_token=' + encodeURIComponent(csrf)
        }).then(function(res) {
            if (!res.ok) { location.reload(); }
        });
    });
});
</script>