@extends('layouts.app')

@section('title', '注册')

@section('content')
    <div class="card stack" style="max-width:520px;">
        <h1>注册（学员）</h1>
        <p class="muted">
            @if(config('practice.registration_requires_approval'))
                注册后需要管理员审核通过才可开始练习。
            @else
                注册成功后将自动进入学员首页。
            @endif
        </p>

        <form method="POST" action="{{ route('register') }}" class="stack">
            @csrf

            <div>
                <label for="username">用户名（登录用）</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                @error('username')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="name">姓名</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="email">邮箱（保留字段，用于联系与找回等）</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password">密码</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password_confirmation">确认密码</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">注册</button>
                <a class="muted" href="{{ route('login') }}">已有账号？去登录</a>
            </div>
        </form>
    </div>
@endsection
