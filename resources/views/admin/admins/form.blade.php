@extends('layouts.app')

@section('title', is_null($user) ? '新建管理员' : '编辑管理员')

@section('content')
<div class="card stack" style="max-width:680px;">
    <h1>{{ is_null($user) ? '新建管理员' : '编辑管理员 #'.$user->id }}</h1>

    <form method="POST" action="{{ $action }}" class="stack">
        @csrf
        @if($method === 'PUT')
        @method('PUT')
        @endif

        <div>
            <label for="username">用户名（登录用）</label>
            <input id="username" type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required>
            @error('username') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="name">姓名</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="email">邮箱</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}">
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        @if(is_null($user))
        <div>
            <label for="password">密码</label>
            <input id="password" type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="password_confirmation">确认密码</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
        </div>
        @else
        <div>
            <label for="password">新密码<span class="muted">（留空不修改）</span></label>
            <input id="password" type="password" name="password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="password_confirmation">确认新密码</label>
            <input id="password_confirmation" type="password" name="password_confirmation">
        </div>
        @endif

        <div>
            <label for="role">角色</label>
            <select id="role" name="role" required>
                @foreach($assignableRoles as $role)
                @php $roleLabel = \App\Models\User::ROLE_LABELS[$role] ?? $role; @endphp
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $roleLabel }}</option>
                @endforeach
            </select>
            @error('role') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div id="managed-org-scope" style="display:none;">
            <label>可管理学员分类范围<span class="muted">（空=全部分类，仅对管理员角色生效）</span></label>
            <div style="max-height:200px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;padding:8px;">
                @foreach($allLeafOrganizationUnits ?? [] as $unit)
                <label style="display:flex;align-items:center;gap:6px;font-weight:normal;margin:2px 0;">
                    <input type="checkbox" name="managed_org_unit_ids[]" value="{{ $unit->id }}"
                        @checked(in_array($unit->id, old('managed_org_unit_ids', $managedOrgUnitIds ?? [])))>
                    {{ $unit->parent?->name }}{{ $unit->name }}
                </label>
                @endforeach
            </div>
        </div>

        <div class="row">
            <button class="btn btn-primary" type="submit">{{ is_null($user) ? '创建' : '保存' }}</button>
            <a class="muted" href="{{ route('admin.admins.index') }}">返回</a>
        </div>
    </form>
</div>

<script>
(function() {
    var roleSelect = document.getElementById('role');
    var managedScope = document.getElementById('managed-org-scope');
    if (!roleSelect || !managedScope) return;

    function toggleScope() {
        managedScope.style.display = roleSelect.value === 'admin' ? '' : 'none';
    }

    roleSelect.addEventListener('change', toggleScope);
    toggleScope();
})();
</script>
@endsection
