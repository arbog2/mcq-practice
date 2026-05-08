<form method="post" action="{{ $action }}">
    @csrf
    @if($method === 'PUT')
    @method('PUT')
    @endif
    <div class="stack">
        <label>用户名<input type="text" name="username" value="{{ $user->username ?? '' }}" required></label>
        <label>姓名<input type="text" name="name" value="{{ $user->name ?? '' }}" required></label>
        <label>邮箱<input type="email" name="email" value="{{ $user->email ?? '' }}" required></label>
        
        @if(is_null($user))
        <label>密码<input type="password" name="password" required></label>
        <label>确认密码<input type="password" name="password_confirmation" required></label>
        @else
        <label>密码<span class="muted">（留空不修改）</span><input type="password" name="password"></label>
        @endif
        
        <label>角色<select name="role" required>
            @foreach($assignableRoles as $role)
            @if(!is_null($user) && $user->role == $role)
            <option value="{{ $role }}" selected>{{ $role === 'student' ? '学员' : '管理员' }}</option>
            @else
            <option value="{{ $role }}">{{ $role === 'student' ? '学员' : '管理员' }}</option>
            @endif
            @endforeach
        </select></label>
        
        <label>用户分类<select name="organization_unit_id">
            <option value="">未分类</option>
            @foreach($leafOrganizationUnits as $unit)
            @if(!is_null($user) && $user->organization_unit_id == $unit->id)
            <option value="{{ $unit->id }}" selected>{{ $unit->parent?->name }} — {{ $unit->name }}</option>
            @else
            <option value="{{ $unit->id }}">{{ $unit->parent?->name }} — {{ $unit->name }}</option>
            @endif
            @endforeach
        </select></label>
    </div>
    <div class="stack" style="margin-top:12px;">
        @if(!is_null($user))
        <button class="btn btn-primary" type="submit">更新</button>
        @else
        <button class="btn btn-primary" type="submit">创建</button>
        @endif
    </div>
</form>