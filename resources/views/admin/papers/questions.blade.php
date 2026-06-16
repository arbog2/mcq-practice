@extends('layouts.app')

@section('title', '组卷 - ' . $examPaper->title)

@section('content')
<div class="stack">
    <div class="card row" style="justify-content:space-between;align-items:center;">
        <div>
            <h1 style="margin:0;">组卷：{{ $examPaper->title }}</h1>
            <p class="muted" style="margin:4px 0 0;">
                当前共 <strong id="qcount">{{ $examPaper->questions->count() }}</strong> 题，
                总分 <strong id="tscore">{{ $examPaper->total_score }}</strong>
            </p>
        </div>
        <a class="btn" href="{{ route('admin.papers.index') }}">← 返回试卷列表</a>
    </div>

    <div class="card stack">
        <h2>从题库选择题目</h2>
        <div class="row" style="flex-wrap:wrap; gap:10px;">
            <label class="row" style="gap:10px; align-items:center;">
                <span class="muted">分类筛选</span>
                <select id="filter-category">
                    <option value="">全部</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="row" style="gap:10px; align-items:center;">
                <span class="muted">题干关键词</span>
                <input type="text" id="filter-keyword" placeholder="搜索题干..." style="width:200px;">
                <button class="btn btn-primary" id="search-btn">搜索</button>
            </label>
        </div>

        <div id="question-bank">
            <p class="muted">请使用上方筛选条件搜索题目。</p>
        </div>
    </div>

    <div class="card stack">
        <div class="row" style="justify-content:space-between;align-items:center;">
            <h2 style="margin:0;">试卷题目</h2>
        </div>
        <div id="paper-questions">
            @forelse ($examPaper->questions as $q)
                <div class="row" style="justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);" data-qid="{{ $q->id }}">
                    <div class="row" style="align-items:center;">
                        <span class="pill" style="cursor:grab; margin-right:8px;">⠿</span>
                        <span style="max-width:500px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ \Illuminate\Support\Str::limit(strip_tags($q->stem), 80) }}</span>
                        <span class="muted" style="margin-left:8px;">({{ $q->score }}分)</span>
                    </div>
                    <button class="btn btn-danger remove-q" data-id="{{ $q->id }}" style="font-size:12px;padding:4px 8px;">移除</button>
                </div>
            @empty
                <p class="muted" id="empty-msg">暂未添加题目。</p>
            @endforelse
        </div>
    </div>
</div>

<script>
(function() {
    var paperId = {{ $examPaper->id }};
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function loadQuestions(page) {
        var catId = document.getElementById('filter-category').value;
        var keyword = document.getElementById('filter-keyword').value;
        page = page || 1;
        var url = '/admin/papers/' + paperId + '/questions/search?category_id=' + encodeURIComponent(catId) + '&keyword=' + encodeURIComponent(keyword) + '&page=' + page;
        document.getElementById('question-bank').innerHTML = '<p class="muted">加载中...</p>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('question-bank').innerHTML = data.html;
                if (data.pagination) {
                    document.getElementById('question-bank').insertAdjacentHTML('beforeend', '<div class="muted" style="margin-top:10px;">' + data.pagination + '</div>');
                }
                bindAddButtons();
            });
    }

    function bindAddButtons() {
        document.querySelectorAll('.add-q-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var ids = [];
                document.querySelectorAll('.q-cb:checked').forEach(function(cb) { ids.push(cb.value); });
                if (ids.length === 0) { alert('请先勾选题目。'); return; }
                btn.disabled = true;
                btn.textContent = '添加中...';
                fetch('/admin/papers/' + paperId + '/questions', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ question_ids: ids })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    btn.disabled = false;
                    btn.textContent = '加入试卷';
                    loadQuestions();
                    location.reload();
                });
            });
        });
    }

    document.getElementById('question-bank').addEventListener('change', function(e) {
        if (e.target.id === 'select-all-q') {
            document.querySelectorAll('.q-cb:not(:disabled)').forEach(function(cb) { cb.checked = e.target.checked; });
        }
    });

    document.getElementById('question-bank').addEventListener('click', function(e) {
        var a = e.target.closest('a[href*="page="]');
        if (a) {
            e.preventDefault();
            var m = a.href.match(/[?&]page=(\d+)/);
            if (m) loadQuestions(parseInt(m[1]));
        }
    });

    document.getElementById('search-btn').addEventListener('click', loadQuestions);
    document.getElementById('filter-category').addEventListener('change', loadQuestions);
    document.getElementById('filter-keyword').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadQuestions();
    });

    document.getElementById('paper-questions').addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-q');
        if (!btn) return;
        if (!confirm('确认移除此题目？')) return;
        var qid = btn.getAttribute('data-id');
        btn.disabled = true;
        fetch('/admin/papers/' + paperId + '/questions/' + qid, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_method=DELETE&_token=' + csrfToken
        }).then(function(r) { return r.json(); }).then(function(data) {
            location.reload();
        });
    });

    loadQuestions();
})();
</script>
@endsection
