@extends('layouts.app')

@section('title', '新建分类')

@section('content')
    <div class="card stack" style="max-width:720px;">
        <h1>新建分类</h1>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="stack">
            @csrf

            <div>
                <label for="name">名称</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="slug">Slug（可留空自动生成）</label>
                <input id="slug" type="text" name="slug" value="{{ old('slug') }}">
                @error('slug') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="sort_order">排序（数字越小越靠前）</label>
                <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                @error('sort_order') <div class="error">{{ $message }}</div> @enderror
            </div>

            <label class="row" style="user-select:none;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                <span class="muted">启用</span>
            </label>

            <div class="row">
                <button class="btn btn-primary" type="submit">保存</button>
                <a class="muted" href="{{ route('admin.categories.index') }}">返回</a>
            </div>
        </form>
    </div>
@endsection
