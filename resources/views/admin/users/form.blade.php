<form method="post" action="{{ $action }}">
    @csrf
    @if($method === 'PUT')
    @method('PUT')
    @endif
    <div class="stack">
        <label>用户名<input type="text" name="username" value="{{ $user->username ?? '' }}" required></label>
        <label>姓名<input type="text" name="name" value="{{ $user->name ?? '' }}" required></label>
        <label>邮箱<input type="email" name="email" value="{{ $user->email ?? '' }}"></label>
        
        @if(is_null($user))
        <label>密码<input type="password" name="password" required></label>
        <label>确认密码<input type="password" name="password_confirmation" required></label>
        @else
        <label>密码<span class="muted">（留空不修改）</span><input type="password" name="password"></label>
        <label>确认密码<input type="password" name="password_confirmation"></label>
        @endif
        
        <label>角色<select name="role" required>
            @foreach($assignableRoles as $role)
            @php $roleLabel = \App\Models\User::ROLE_LABELS[$role] ?? $role; @endphp
            <option value="{{ $role }}" @selected(!is_null($user) && $user->role == $role)>{{ $roleLabel }}</option>
            @endforeach
        </select></label>
        
        <label>学员分类<select name="organization_unit_id">
            <option value="">未分类</option>
            @foreach($leafOrganizationUnits as $unit)
            @php $selected = !is_null($user) && $user->organization_unit_id == $unit->id; @endphp
            <option value="{{ $unit->id }}" @selected($selected)>{{ $unit->parent?->name }}{{ $unit->name }}</option>
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