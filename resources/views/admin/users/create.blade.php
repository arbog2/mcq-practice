@extends('layouts.app')

@section('title', '新建用户')

@section('content')
    <div class="card stack" style="max-width:720px;">
        <h1>新建用户</h1>
        <p class="muted">由管理员创建的用户默认审核通过。学员可绑定到二级用户分类（如：高二 / 三班）。</p>

        <form method="POST" action="{{ route('admin.users.store') }}" class="stack">
            @csrf

            <div>
                <label for="username">用户名（登录用）</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username">
                @error('username') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="name">姓名</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="email">邮箱</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="password">密码</label>
                <input id="password" type="password" name="password" required>
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="password_confirmation">确认密码</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <div>
                <label for="role">角色</label>
                <select id="role" name="role" required>
                    @foreach ($assignableRoles as $role)
                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                @error('role') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="organization_unit_id">用户分类（二级，可选）</label>
                <select id="organization_unit_id" name="organization_unit_id">
                    <option value="">— 不绑定 —</option>
                    @foreach ($leafOrganizationUnits as $unit)
                        <option value="{{ $unit->id }}" @selected((string)old('organization_unit_id') === (string)$unit->id)>
                            {{ $unit->fullLabel() }}
                        </option>
                    @endforeach
                </select>
                @error('organization_unit_id') <div class="error">{{ $message }}</div> @enderror
                <div class="muted">请先在「用户分类」菜单维护一级/二级。</div>
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">创建</button>
                <a class="muted" href="{{ route('admin.users.index') }}">返回</a>
            </div>
        </form>
    </div>
@endsection
