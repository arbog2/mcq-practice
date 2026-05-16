@extends('layouts.app')

@section('title', '个人资料')

@section('content')
<div class="card stack" style="max-width:480px;">
    <h1>个人资料</h1>

    <form method="POST" action="{{ route('admin.profile.update') }}" class="stack">
        @csrf
        @method('PUT')

        <div>
            <label for="username">用户名（不可修改）</label>
            <input id="username" type="text" value="{{ $user->username }}" disabled>
        </div>

        <div>
            <label for="name">姓名</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="role">角色</label>
            <input id="role" type="text" value="{{ \App\Models\User::ROLE_LABELS[$user->role] ?? $user->role }}" disabled>
        </div>

        <div>
            <label for="password">新密码（留空则不修改）</label>
            <input id="password" type="password" name="password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="password_confirmation">确认新密码</label>
            <input id="password_confirmation" type="password" name="password_confirmation">
        </div>

        <div>
            <button class="btn btn-primary" type="submit">保存</button>
        </div>
    </form>
</div>
@endsection
