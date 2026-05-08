@extends('layouts.app')

@section('title', '题库管理')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:flex-end;">
            <div>
                <h1 style="margin:0;">题库管理</h1>
                <p class="muted" style="margin:6px 0 0;">题干 + 选项（固定4个选项）</p>
            </div>
            <div class="row">
                <a class="btn" href="{{ route('admin.questions.import') }}">Excel导入</a>
                <button class="btn btn-primary" onclick="openAjaxModal('{{ route('admin.questions.create') }}', '新建题目')">新建题目</button>
            </div>
        </div>

        <div class="card row" style="flex-wrap:wrap; gap:10px;">
            <form method="GET" action="{{ route('admin.questions.index') }}" class="row" style="gap:10px;">
                <label class="row" style="gap:10px; align-items:center;">
                    <span class="muted">分类筛选</span>
                    <select name="category_id" onchange="this.form.submit()">
                        <option value="">全部</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)$categoryId === (string)$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div id="batch-bar" class="card row" style="display:none;justify-content:space-between;align-items:center;">
            <span class="muted" id="batch-count">已选择 0 项</span>
            <label class="row" style="gap:10px;align-items:center;">
                <span class="muted">批量转移到</span>
                <select id="batch-category" style="width:auto;">
                    <option value="">请选择</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" id="batch-move-btn" disabled>确认转移</button>
            </label>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>ID</th>
                        <th>分类</th>
                        <th>题干</th>
                        <th>启用</th>
                        <th style="text-align:right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questions as $question)
                        <tr>
                            <td><input type="checkbox" class="q-checkbox" value="{{ $question->id }}"></td>
                            <td>{{ $question->id }}</td>
                            <td><span class="q-cat">{{ $question->category->name }}</span></td>
                            <td style="max-width:350px;">{{ \Illuminate\Support\Str::limit($question->stem, 80) }}</td>
                            <td>{{ $question->is_active ? '是' : '否' }}</td>
                            <td style="text-align:right;">
                                <button class="btn btn-primary" onclick="openAjaxModal('{{ route('admin.questions.move.form', $question) }}', '转移分类')" style="font-size:12px;padding:4px 8px;">转移</button>
                                <button class="btn" onclick="openAjaxModal('{{ route('admin.questions.edit', $question) }}', '编辑题目')" style="font-size:12px;padding:4px 8px;">编辑</button>
                                <button class="btn btn-danger" onclick="deleteQuestion({{ $question->id }})" style="font-size:12px;padding:4px 8px;">删除</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $questions->withQueryString()->links() }}</div>
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
        var checkboxes = document.querySelectorAll('.q-checkbox');
        var batchBar = document.getElementById('batch-bar');
        var batchCount = document.getElementById('batch-count');
        var batchCategory = document.getElementById('batch-category');
        var batchMoveBtn = document.getElementById('batch-move-btn');

        function updateBatchBar() {
            var checked = document.querySelectorAll('.q-checkbox:checked');
            var count = checked.length;
            if (count > 0) {
                batchBar.style.display = '';
                batchCount.textContent = '已选择 ' + count + ' 项';
            } else {
                batchBar.style.display = 'none';
            }
            batchMoveBtn.disabled = count === 0 || !batchCategory.value;
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

        batchCategory.addEventListener('change', updateBatchBar);

        batchMoveBtn.addEventListener('click', function() {
            var ids = [];
            document.querySelectorAll('.q-checkbox:checked').forEach(function(cb) { ids.push(cb.value); });
            var catId = batchCategory.value;
            if (ids.length === 0 || !catId) return;
            if (!confirm('确认将 ' + ids.length + ' 道题目转移到所选分类？')) return;
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            batchMoveBtn.disabled = true;
            batchMoveBtn.textContent = '转移中...';
            fetch('{{ route('admin.questions.batch-move') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids: ids, category_id: catId })
            }).then(function(res) { return res.json(); }).then(function(data) {
                if (data.reload) { location.reload(); }
                else { alert(data.message || '操作完成'); location.reload(); }
            }).catch(function() { alert('转移失败'); location.reload(); });
        });
    })();
    </script>
@endsection