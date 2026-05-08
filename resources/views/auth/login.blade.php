@extends('layouts.app')

@section('title', '登录')

@section('content')
    <div class="card stack" style="max-width:520px;">
        <h1>登录</h1>
        <p class="muted">使用<strong>用户名</strong>与密码登录（邮箱不再作为登录账号）。</p>

        <form method="POST" action="{{ route('login') }}" class="stack">
            @csrf

            <div>
                <label for="username">用户名</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                @error('username')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password">密码</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <label class="row" style="user-select:none;">
                <input type="checkbox" name="remember" value="1">
                <span class="muted">记住我</span>
            </label>

            <div class="row">
                <button class="btn btn-primary" type="submit">登录</button>
                @if(config('practice.registration_enabled'))
                    <a class="muted" href="{{ route('register') }}">没有账号？去注册</a>
                @endif
            </div>
        </form>
    </div>
@endsection
