@extends('layouts.app')

@section('title', '管理员管理')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:flex-end;">
            <div class="stack" style="gap:6px;">
                <h1 style="margin:0;">管理员管理</h1>
                <p class="muted" style="margin:0;">管理 admin 和 super_admin 角色用户</p>
            </div>
            <div class="row">
                <a class="btn btn-primary" href="{{ route('admin.admins.create') }}">新建管理员</a>
            </div>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">筛选</h2>
            <form method="GET" action="{{ route('admin.admins.index') }}" class="stack">
                <div class="row" style="align-items:flex-end; flex-wrap:wrap; gap:12px;">
                    <div style="min-width:200px;">
                        <label>姓名搜索</label>
                        <input type="text" name="name" value="{{ $nameSearch }}" placeholder="输入姓名搜索...">
                    </div>
                    <div><button class="btn" type="submit">筛选</button></div>
                </div>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>用户名</th>
                        <th>姓名</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>管理范围</th>
                        <th style="text-align:right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $adminUser)
                    <tr>
                        <td>{{ $adminUser->username }}</td>
                        <td>{{ $adminUser->name }}</td>
                        <td>{{ $adminUser->email }}</td>
                        <td>{{ \App\Models\User::ROLE_LABELS[$adminUser->role] ?? $adminUser->role }}</td>
                        <td>
                            @if($adminUser->role === \App\Models\User::ROLE_ADMIN && is_array($adminUser->managed_org_unit_ids))
                                @if(empty($adminUser->managed_org_unit_ids))
                                    <span class="pill">全部学员</span>
                                @else
                                    @php
                                        $names = \App\Models\OrganizationUnit::whereIn('id', $adminUser->managed_org_unit_ids)
                                            ->with('parent')->get()
                                            ->map(fn($u) => ($u->parent?->name ?? '').$u->name)
                                            ->join('、');
                                    @endphp
                                    <span class="pill">{{ $names }}</span>
                                @endif
                            @elseif($adminUser->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                <span class="pill">全部</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <a class="btn" href="{{ route('admin.admins.edit', ['admin' => $adminUser]) }}">编辑</a>
                            <button class="btn btn-danger" onclick="deleteAdmin({{ $adminUser->id }})">删除</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="row" style="justify-content:space-between;align-items:center;">
            <div class="muted">每页
                <select id="per-page" onchange="var p=new URLSearchParams(location.search);p.set('per_page',this.value);p.delete('page');location.search=p.toString()" style="width:auto;display:inline-block;padding:4px 8px;">
                    @foreach([10,20,50,80,100] as $n)
                        <option value="{{ $n }}" @if($perPage == $n) selected @endif>{{ $n }}</option>
                    @endforeach
                </select>
                条，共 {{ $users->total() }} 个管理员
            </div>
            <div>{{ $users->withQueryString()->links() }}</div>
        </div>
    </div>

    <script>
    function deleteAdmin(id) {
        if (!confirm('确认删除此管理员？此操作不可撤销。')) return;
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch('/admin/admins/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: '_method=DELETE&_token=' + csrf
        }).then(function(res) {
            if (res.ok) { location.reload(); }
            else { alert('删除失败'); }
        });
    }
    </script>
@endsection
