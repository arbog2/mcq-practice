@extends('layouts.app')

@section('title', '题库 Excel 导入')

@section('content')
    <div class="card stack" style="max-width:920px;">
        <h1>题库 Excel 导入</h1>
        <p class="muted">
            第一行必须为英文表头（见模板）。<strong>category_slug</strong> 对应「分类管理」里的 slug（例如 Seeder 默认的 <code>php-basics</code>）。
            <strong>correct</strong> 填正确答案：<code>A</code>/<code>B</code>/<code>C</code>/<code>D</code> 或 <code>0</code>-<code>3</code>（依次对应 A-D）。
            <strong>difficulty</strong> 为 1-5 或留空；<strong>is_active</strong> 留空默认启用，可填 1/0、是/否等。
        </p>

        @if($categories->isNotEmpty())
            <div class="card stack" style="background:#fafafa;">
                <div class="muted" style="margin:0;">当前已有分类 slug（导入时请与此一致）：</div>
                <ul style="margin:0; padding-left:18px;">
                    @foreach ($categories as $c)
                        <li><code>{{ $c->slug }}</code> — {{ $c->name }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @error('file')
            <div class="error">{{ $message }}</div>
        @enderror

        <div class="row">
            <a class="btn btn-primary" href="{{ route('admin.questions.import.template') }}">下载导入模板（xlsx）</a>
        </div>

        <form method="POST" action="{{ route('admin.questions.import.store') }}" enctype="multipart/form-data" class="stack">
            @csrf

            <div>
                <label for="file">选择 Excel 文件（xlsx / xls / csv）</label>
                <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">开始导入</button>
                <a class="muted" href="{{ route('admin.questions.index') }}">返回题库列表</a>
            </div>
        </form>
    </div>
@endsection
