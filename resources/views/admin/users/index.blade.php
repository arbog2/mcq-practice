@extends('layouts.app')

@section('title', '用户管理')

@section('content')
@php $filterBase = request()->except(['page', 'role', 'approval_status']); @endphp

    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:flex-end;">
            <div class="stack" style="gap:6px;">
                <h1 style="margin:0;">用户管理</h1>
                <p class="muted" style="margin:0;">登录账号为<strong>用户名</strong></p>
            </div>
            <div class="row">
                <a class="btn" href="{{ route('admin.users.import') }}">Excel导入</a>
                <button class="btn btn-primary" onclick="openAjaxModal('{{ route('admin.users.create') }}', '新建用户')">新建用户</button>
            </div>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">筛选</h2>
            <form method="GET" action="{{ route('admin.users.index') }}" class="stack">
                @if($role)<input type="hidden" name="role" value="{{ $role }}">@endif
                @if($approvalStatus)<input type="hidden" name="approval_status" value="{{ $approvalStatus }}">@endif
                <div class="row" style="align-items:flex-end; flex-wrap:wrap; gap:12px;">
                    <div style="min-width:200px;">
                        <label>一级用户分类</label>
                        <select name="org_level1_id" id="org_level1_id">
                            <option value="">全部</option>
                            @foreach($rootOrganizationUnits as $root)
                            <option value="{{ $root->id }}" @selected((string)$orgLevel1Id === (string)$root->id)>{{ $root->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="min-width:260px;">
                        <label>二级用户分类</label>
                        <select name="org_level2_id" id="org_level2_id">
                            <option value="">全部</option>
                            @foreach($rootOrganizationUnits as $root)
                            @foreach($root->children as $child)
                            <option value="{{ $child->id }}" data-parent="{{ $root->id }}" @selected((string)$orgLevel2Id === (string)$child->id)>{{ $root->name }} — {{ $child->name }}</option>
                            @endforeach
                            @endforeach
                        </select>
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
                        <th>分类</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th style="text-align:right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->organizationUnit?->fullLabel() }}</td>
                        <td>{{ $user->role }}</td>
                        <td>{{ $user->approval_status }}</td>
                        <td style="text-align:right;">
                            @if($user->role === 'student' && $user->approval_status === 'pending')
                            <button class="btn btn-primary" onclick="approveUser({{ $user->id }})">通过</button>
                            <button class="btn" onclick="rejectUser({{ $user->id }})">拒绝</button>
                            @endif
                            @can('update', $user)
                            <button class="btn" onclick="openAjaxModal('{{ route('admin.users.edit', $user) }}', '编辑用户')">编辑</button>
                            @endcan
                            @can('delete', $user)
                            <button class="btn btn-danger" onclick="deleteUser({{ $user->id }})">删除</button>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="muted">{{ $users->withQueryString()->links() }}</div>
    </div>

    <div id="ajax-modal" class="modal">
        <div class="modal-backdrop" onclick="closeAjaxModal()"></div>
        <div class="modal-content">
            <div class="modal-header"><h3 id="ajax-modal-title"></h3><button class="modal-close" onclick="closeAjaxModal()">&times;</button></div>
            <div class="modal-body" id="ajax-modal-body"></div>
        </div>
    </div>
@endsection