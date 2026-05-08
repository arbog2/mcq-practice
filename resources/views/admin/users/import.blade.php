@extends('layouts.app')

@section('title', '批量导入用户')

@section('content')
    <div class="card stack" style="max-width:920px;">
        <h1>Excel 批量导入学员</h1>
        <p class="muted">
            第一行必须为英文表头：<code>username</code>、<code>email</code>、<code>name</code>、<code>password</code>、<code>level_1</code>（一级，如高二）、<code>level_2</code>（二级，如三班）。
            一级与二级可同时留空；若填写必须两行都填。导入角色固定为学员且默认<strong>已通过审核</strong>。
        </p>

        @error('file')
            <div class="error">{{ $message }}</div>
        @enderror

        <div class="row">
            <a class="btn btn-primary" href="{{ route('admin.users.import.template') }}">下载导入模板（xlsx）</a>
        </div>

        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data" class="stack">
            @csrf

            <div>
                <label for="file">选择 Excel 文件（xlsx / xls / csv）</label>
                <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">开始导入</button>
                <a class="muted" href="{{ route('admin.users.index') }}">返回用户列表</a>
            </div>
        </form>
    </div>
@endsection
