@extends('layouts.app')

@section('title', '系统设置')

@section('content')
    <div class="card stack">
        <h1>系统设置</h1>

        <form method="post" action="{{ route('admin.settings.update') }}">
            @csrf

            <div class="stack">
                <label>
                    <input type="hidden" name="registration_enabled" value="0">
                    <input type="checkbox" name="registration_enabled" value="1" @if($registrationEnabled) checked @endif>
                    开启自助注册
                </label>
                <p class="muted">允许新用户在注册页面自行注册账号。</p>

                <label>
                    <input type="hidden" name="registration_requires_approval" value="0">
                    <input type="checkbox" name="registration_requires_approval" value="1" @if($registrationRequiresApproval) checked @endif>
                    注册需审核
                </label>
                <p class="muted">新注册的用户需要管理员审核后才能使用系统。</p>
            </div>

            <div class="stack">
                <hr style="border:none;border-top:1px solid var(--border);margin:12px 0;">
                <label>
                    <span class="muted">每次练习题量</span>
                    <input type="number" name="questions_per_session" value="{{ $questionsPerSession }}" min="1" max="100" style="max-width:120px;">
                </label>
                <p class="muted">每次练习抽取的题目数量。</p>
            </div>

            <div class="stack">
                <button class="btn btn-primary" type="submit">保存设置</button>
            </div>
        </form>
    </div>
@endsection