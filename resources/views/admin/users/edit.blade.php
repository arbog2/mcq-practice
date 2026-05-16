@extends('layouts.app')

@section('title', '编辑用户')

@section('content')
    <div class="card stack" style="max-width:720px;">
        <h1>编辑用户 #{{ $user->id }}</h1>

        <form method="POST" action="{{ url('admin/students/' . $user->id) }}" class="stack">
            @csrf
            @method('PUT')

            <div>
                <label for="username">用户名（登录用）</label>
                <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}" required autocomplete="username">
                @error('username') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="name">姓名</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="email">邮箱</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="error">{{ $message }}</div> @enderror
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
                <label for="role">角色</label>
                <select id="role" name="role" required>
                    @foreach ($assignableRoles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                @error('role') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="organization_unit_id">用户分类（二级，可选）</label>
                <select id="organization_unit_id" name="organization_unit_id">
                    <option value="">— 不绑定 —</option>
                    @foreach ($leafOrganizationUnits as $unit)
                        <option value="{{ $unit->id }}" @selected((string)old('organization_unit_id', $user->organization_unit_id) === (string)$unit->id)>
                            {{ $unit->fullLabel() }}
                        </option>
                    @endforeach
                </select>
                @error('organization_unit_id') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="muted">
                当前审核状态：<strong>{{ $user->approval_status }}</strong>
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">保存</button>
                <a class="muted" href="{{ route('admin.users.index') }}">返回</a>
            </div>
        </form>
    </div>
@endsection
