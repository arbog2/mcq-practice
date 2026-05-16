@extends('layouts.app')

@section('title', '学员管理')

@section('content')
@php $filterBase = request()->except(['page', 'approval_status']); @endphp

    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:flex-end;">
            <div class="stack" style="gap:6px;">
                <h1 style="margin:0;">学员管理</h1>
                <p class="muted" style="margin:0;">登录账号为<strong>用户名</strong></p>
            </div>
            <div class="row">
                <a class="btn" href="{{ route('admin.users.import') }}">Excel导入</a>
                <button class="btn btn-primary" onclick="openAjaxModal('{{ route('admin.users.create') }}', '新建学员')">新建学员</button>
            </div>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">筛选</h2>
            <form method="GET" action="{{ route('admin.users.index') }}" class="stack">
                @if($approvalStatus)<input type="hidden" name="approval_status" value="{{ $approvalStatus }}">@endif
                <div class="row" style="align-items:flex-end; flex-wrap:wrap; gap:12px;">
                    <div style="min-width:200px;">
                        <label>一级学员分类</label>
                        <select name="org_level1_id" id="org_level1_id">
                            <option value="">全部</option>
                            @foreach($rootOrganizationUnits as $root)
                            <option value="{{ $root->id }}" @selected((string)$orgLevel1Id === (string)$root->id)>{{ $root->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="min-width:260px;">
                        <label>二级学员分类</label>
                        <select name="org_level2_id" id="org_level2_id">
                            <option value="">全部</option>
                            @foreach($rootOrganizationUnits as $root)
                            @foreach($root->children as $child)
                            <option value="{{ $child->id }}" data-parent="{{ $root->id }}" @selected((string)$orgLevel2Id === (string)$child->id)>{{ $root->name }}{{ $child->name }}</option>
                            @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div><button class="btn" type="submit">筛选</button></div>
                </div>
            </form>
        </div>

        <div id="batch-bar" class="card row" style="display:none;justify-content:space-between;align-items:center;">
            <span class="muted" id="batch-count">已选择 0 项</span>
            <div class="row" style="gap:10px;align-items:center;">
                <label class="row" style="gap:10px;align-items:center;">
                    <span class="muted">批量转移到</span>
                    <select id="batch-org-unit" style="width:auto;">
                        <option value="">未分类</option>
                        @foreach($rootOrganizationUnits as $root)
                        @foreach($root->children as $child)
                        <option value="{{ $child->id }}">{{ $root->name }}{{ $child->name }}</option>
                        @endforeach
                        @endforeach
                    </select>
                    <button class="btn btn-primary" id="batch-move-btn" disabled>转移</button>
                </label>
                <button class="btn btn-danger" id="batch-delete-btn" disabled>删除</button>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
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
                        <td><input type="checkbox" class="user-checkbox" value="{{ $user->id }}"></td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->organizationUnit?->fullLabel() }}</td>
                        <td>{{ \App\Models\User::ROLE_LABELS[$user->role] ?? $user->role }}</td>
                        <td>{{ $user->approval_status }}</td>
                        <td style="text-align:right;">
                            @if($user->role === 'student' && $user->approval_status === 'pending')
                            <button class="btn btn-primary" onclick="approveUser({{ $user->id }})">通过</button>
                            <button class="btn" onclick="rejectUser({{ $user->id }})">拒绝</button>
                            @endif
                            @if(auth()->user()->canManageUser($user))
                            <button class="btn" onclick="openAjaxModal('{{ route('admin.users.edit', ['student' => $user]) }}', '编辑学员')">编辑</button>
                            <button class="btn btn-danger" onclick="deleteUser({{ $user->id }})">删除</button>
                            @endif
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
                条，共 {{ $users->total() }} 个学员
            </div>
            <div>{{ $users->withQueryString()->links() }}</div>
        </div>
    </div>

    <div id="ajax-modal" class="modal">
        <div class="modal-backdrop" onclick="closeAjaxModal()"></div>
        <div class="modal-content">
            <div class="modal-header"><h3 id="ajax-modal-title"></h3><button class="modal-close" onclick="closeAjaxModal()">&times;</button></div>
            <div class="modal-body" id="ajax-modal-body"></div>
        </div>
    </div>

    <script>
    (function() {
        var selectAll = document.getElementById('select-all');
        var checkboxes = document.querySelectorAll('.user-checkbox');
        var batchBar = document.getElementById('batch-bar');
        var batchCount = document.getElementById('batch-count');
        var batchOrgUnit = document.getElementById('batch-org-unit');
        var batchMoveBtn = document.getElementById('batch-move-btn');
        var batchDeleteBtn = document.getElementById('batch-delete-btn');
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function getCheckedIds() {
            var ids = [];
            document.querySelectorAll('.user-checkbox:checked').forEach(function(cb) { ids.push(cb.value); });
            return ids;
        }

        function updateBatchBar() {
            var ids = getCheckedIds();
            var count = ids.length;
            if (count > 0) {
                batchBar.style.display = '';
                batchCount.textContent = '已选择 ' + count + ' 项';
            } else {
                batchBar.style.display = 'none';
            }
            batchMoveBtn.disabled = count === 0;
            batchDeleteBtn.disabled = count === 0;
        }

        function postJson(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body)
            }).then(function(res) { return res.json(); });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
                updateBatchBar();
            });
        }
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', updateBatchBar);
        });

        batchMoveBtn.addEventListener('click', function() {
            var ids = getCheckedIds();
            var orgId = batchOrgUnit.value;
            if (ids.length === 0) return;
            if (!confirm('确认将 ' + ids.length + ' 个用户转移到所选分类？')) return;
            batchMoveBtn.disabled = true;
            batchMoveBtn.textContent = '转移中...';
            postJson('{{ route('admin.users.batch-move') }}', { ids: ids, organization_unit_id: orgId })
                .then(function(data) { location.reload(); })
                .catch(function() { alert('转移失败'); location.reload(); });
        });

        batchDeleteBtn.addEventListener('click', function() {
            var ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm('确认删除选中的 ' + ids.length + ' 个用户？此操作不可撤销。')) return;
            batchDeleteBtn.disabled = true;
            batchDeleteBtn.textContent = '删除中...';
            postJson('{{ route('admin.users.batch-destroy') }}', { ids: ids })
                .then(function(data) { location.reload(); })
                .catch(function() { alert('删除失败'); location.reload(); });
        });
    })();
    </script>
@endsection