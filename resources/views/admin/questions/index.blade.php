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
            <form method="GET" action="{{ route('admin.questions.index') }}" class="row" style="gap:10px; flex-wrap:wrap;">
                <label class="row" style="gap:10px; align-items:center;">
                    <span class="muted">分类筛选</span>
                    <select name="category_id" onchange="this.form.submit()">
                        <option value="">全部</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)$categoryId === (string)$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="row" style="gap:10px; align-items:center;">
                    <span class="muted">题干关键词</span>
                    <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="搜索题干..." style="width:200px;">
                    <button class="btn btn-primary" type="submit">搜索</button>
                    @if ($keyword)
                        <a class="btn" href="{{ route('admin.questions.index', ['category_id' => $categoryId]) }}">清除</a>
                    @endif
                </label>
            </form>
        </div>

        <div id="batch-bar" class="card row" style="display:none;justify-content:space-between;align-items:center;">
            <span class="muted" id="batch-count">已选择 0 项</span>
            <div class="row" style="gap:10px;align-items:center;">
                <label class="row" style="gap:10px;align-items:center;">
                    <span class="muted">批量转移到</span>
                    <select id="batch-category" style="width:auto;">
                        <option value="">请选择</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
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

        <div class="row" style="justify-content:space-between;align-items:center;">
            <div class="muted">每页
                <select id="per-page" onchange="var p=new URLSearchParams(location.search);p.set('per_page',this.value);p.delete('page');location.search=p.toString()" style="width:auto;display:inline-block;padding:4px 8px;">
                    @foreach([10,20,40,50,100] as $n)
                        <option value="{{ $n }}" @if($perPage == $n) selected @endif>{{ $n }}</option>
                    @endforeach
                </select>
                条，共 {{ $questions->total() }} 道
            </div>
            <div>{{ $questions->withQueryString()->links() }}</div>
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
        var checkboxes = document.querySelectorAll('.q-checkbox');
        var batchBar = document.getElementById('batch-bar');
        var batchCount = document.getElementById('batch-count');
        var batchCategory = document.getElementById('batch-category');
        var batchMoveBtn = document.getElementById('batch-move-btn');
        var batchDeleteBtn = document.getElementById('batch-delete-btn');
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function getCheckedIds() {
            var ids = [];
            document.querySelectorAll('.q-checkbox:checked').forEach(function(cb) { ids.push(cb.value); });
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
            batchMoveBtn.disabled = count === 0 || !batchCategory.value;
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

        batchCategory.addEventListener('change', updateBatchBar);

        batchMoveBtn.addEventListener('click', function() {
            var ids = getCheckedIds();
            var catId = batchCategory.value;
            if (ids.length === 0 || !catId) return;
            if (!confirm('确认将 ' + ids.length + ' 道题目转移到所选分类？')) return;
            batchMoveBtn.disabled = true;
            batchMoveBtn.textContent = '转移中...';
            postJson('{{ route('admin.questions.batch-move') }}', { ids: ids, category_id: catId })
                .then(function(data) { location.reload(); })
                .catch(function() { alert('转移失败'); location.reload(); });
        });

        batchDeleteBtn.addEventListener('click', function() {
            var ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm('确认删除选中的 ' + ids.length + ' 道题目？此操作不可撤销。')) return;
            batchDeleteBtn.disabled = true;
            batchDeleteBtn.textContent = '删除中...';
            postJson('{{ route('admin.questions.batch-destroy') }}', { ids: ids })
                .then(function(data) { location.reload(); })
                .catch(function() { alert('删除失败'); location.reload(); });
        });
    })();
    </script>
@endsection