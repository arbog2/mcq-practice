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
            <a class="btn btn-primary" href="{{ route('student.papers.index') }}">试卷练习</a>
            <a class="btn" href="{{ route('student.wrong-book') }}">错题本</a>
            <a class="btn" href="{{ route('student.papers.history') }}">练习历史</a>
        </div>
    </div>
@endsection
