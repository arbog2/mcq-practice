@extends('layouts.app')

@section('title', '学员首页')

@section('content')
    <div class="card stack">
        <h1>欢迎，{{ auth()->user()->name }}</h1>
        <p class="muted">
            登录用户名：<strong>{{ auth()->user()->username ?? '—' }}</strong>
            @if(auth()->user()->organizationUnit)
                · 用户分类：<strong>{{ auth()->user()->organizationUnit->fullLabel() }}</strong>
            @endif
        </p>
        <p class="muted">选择下方入口开始练习，或在完成后查看错题本巩固薄弱点。</p>

        <div class="row">
            <a class="btn btn-primary" href="{{ route('student.categories') }}">选择分类开始练习</a>
            <a class="btn" href="{{ route('student.wrong-book') }}">错题本</a>
        </div>

        <div class="muted">
            每次题量：{{ (int) \App\Models\Setting::get('questions_per_session', config('practice.questions_per_session')) }} 题（可在后台"系统设置"调整）。
        </div>
    </div>
@endsection
