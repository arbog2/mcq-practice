@extends('layouts.app')

@section('title', '操作日志')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1 style="margin:0;">操作日志</h1>
        </div>

        <div class="card stack">
            <form method="GET" action="{{ route('admin.logs.index') }}" class="row" style="flex-wrap:wrap; gap:10px;">
                <label class="row" style="gap:10px; align-items:center;">
                    <span class="muted">分类</span>
                    <select name="type">
                        <option value="">全部</option>
                        <option value="auth" @selected($type === 'auth')>登录</option>
                        <option value="user" @selected($type === 'user')>用户管理</option>
                        <option value="question" @selected($type === 'question')>题库管理</option>
                    </select>
                </label>
                <label class="row" style="gap:10px; align-items:center;">
                    <span class="muted">操作人</span>
                    <select name="user_id">
                        <option value="">全部</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((string)$userId === (string)$u->id)>{{ $u->name }} ({{ $u->username }})</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn btn-primary" type="submit">筛选</button>
                <a class="btn" href="{{ route('admin.logs.index') }}">清除</a>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th style="width:160px;">时间</th>
                        <th style="width:120px;">操作人</th>
                        <th style="width:80px;">分类</th>
                        <th>操作</th>
                        <th>描述</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td>
                                @switch($log->type)
                                    @case('auth') 登录 @break
                                    @case('user') 用户管理 @break
                                    @case('question') 题库管理 @break
                                    @default {{ $log->type }}
                                @endswitch
                            </td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $logs->withQueryString()->links() }}</div>
    </div>
@endsection
