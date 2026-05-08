@extends('layouts.app')

@section('title', '后台首页')

@section('content')
    <div class="card stack">
        <h1>后台</h1>
        <p class="muted">从这里管理分类、题库、用户与用户分类，并查看错题统计。题目插图需确保已执行 <code>php artisan storage:link</code>。</p>

        <div class="stack">
            <div class="card stack">
                <h2>常用入口</h2>
                <div class="row">
                    <a class="btn btn-primary" href="{{ route('admin.categories.index') }}">分类管理</a>
                    <a class="btn btn-primary" href="{{ route('admin.questions.index') }}">题库管理</a>
                    <a class="btn btn-primary" href="{{ route('admin.users.index') }}">用户管理</a>
                    <a class="btn btn-primary" href="{{ route('admin.users.import') }}">用户 Excel 导入</a>
                    <a class="btn btn-primary" href="{{ route('admin.organization-units.index') }}">用户分类</a>
                    <a class="btn btn-primary" href="{{ route('admin.stats.wrong-by-category') }}">错题统计</a>
                    <a class="btn btn-primary" href="{{ route('admin.settings.index') }}">系统设置</a>
                </div>
            </div>

            <div class="card stack">
                <h2>注册开关状态</h2>
                <div class="muted">
                    @if(\App\Models\Setting::get('registration_enabled', false))
                        自助注册：已开启
                    @else
                        自助注册：已关闭
                    @endif
                    •
                    @if(\App\Models\Setting::get('registration_requires_approval', false))
                        注册需审核：是
                    @else
                        注册需审核：否
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
