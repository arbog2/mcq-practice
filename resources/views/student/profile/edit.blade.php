@extends('layouts.app')

@section('title', '个人资料')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1>个人资料</h1>

            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('student.profile.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <label>姓名</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label>邮箱</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}">
                    @error('email')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label>新密码（留空不修改）</label>
                    <input type="password" name="password" autocomplete="new-password">
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label>确认新密码</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </div>

                <div class="row">
                    <button class="btn btn-primary" type="submit">保存</button>
                    <a class="btn" href="{{ route('student.dashboard') }}">返回</a>
                </div>
            </form>
        </div>
    </div>
@endsection
