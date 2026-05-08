@extends('layouts.app')

@section('title', '用户分类（二级）')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1>用户分类（二级）</h1>
            <p class="muted">一级示例：<strong>高二</strong>；二级示例：<strong>三班</strong>（归属在某一级下）。用户绑定到<strong>二级</strong>。</p>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">新增一级</h2>
            <form method="POST" action="{{ route('admin.organization-units.store') }}" class="stack">
                @csrf
                <div class="row" style="align-items:flex-end;">
                    <div style="flex:1; min-width:220px;">
                        <label for="root_name">名称</label>
                        <input id="root_name" type="text" name="name" required placeholder="例如：高二">
                        @error('name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div style="width:140px;">
                        <label for="root_sort">排序</label>
                        <input id="root_sort" type="number" name="sort_order" value="0" min="0">
                    </div>
                    <button class="btn btn-primary" type="submit">保存一级</button>
                </div>
            </form>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">新增二级</h2>
            <form method="POST" action="{{ route('admin.organization-units.store') }}" class="stack">
                @csrf
                <div class="row" style="align-items:flex-end;">
                    <div style="flex:1; min-width:220px;">
                        <label for="parent_id">所属一级</label>
                        <select id="parent_id" name="parent_id" required>
                            <option value="">请选择</option>
                            @foreach ($roots as $root)
                                <option value="{{ $root->id }}">{{ $root->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div style="flex:1; min-width:220px;">
                        <label for="child_name">二级名称</label>
                        <input id="child_name" type="text" name="name" required placeholder="例如：三班">
                    </div>
                    <div style="width:140px;">
                        <label for="child_sort">排序</label>
                        <input id="child_sort" type="number" name="sort_order" value="0" min="0">
                    </div>
                    <button class="btn btn-primary" type="submit">保存二级</button>
                </div>
            </form>
        </div>

        @error('delete')
            <div class="status" style="border-color:#fecaca; color:#991b1b;">{{ $message }}</div>
        @enderror

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>一级</th>
                        <th>二级</th>
                        <th style="text-align:right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roots as $root)
                        @if ($root->children->isEmpty())
                            <tr>
                                <td><strong>{{ $root->name }}</strong></td>
                                <td class="muted">（暂无二级）</td>
                                <td style="text-align:right;">
                                    <form method="POST" action="{{ route('admin.organization-units.destroy', $root) }}" style="display:inline;" onsubmit="return confirm('确认删除一级？');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">删除一级</button>
                                    </form>
                                </td>
                            </tr>
                        @else
                            @foreach ($root->children as $child)
                                <tr>
                                    <td><strong>{{ $root->name }}</strong></td>
                                    <td>{{ $child->name }}</td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="{{ route('admin.organization-units.destroy', $child) }}" style="display:inline;" onsubmit="return confirm('确认删除二级？');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit">删除二级</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="3" class="muted">暂无分类。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
