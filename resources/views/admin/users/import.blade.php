@extends('layouts.app')

@section('title', '批量导入用户')

@section('content')
    <div class="card stack" style="max-width:920px;">
        <h1>Excel 批量导入学员</h1>
        <p class="muted">
            第一行必须为英文表头：<code>username</code>、<code>email</code>、<code>name</code>、<code>password</code>、<code>level_1</code>（一级，如高二）、<code>level_2</code>（二级，如三班）。
            一级与二级可同时留空；若填写必须两行都填。导入角色固定为学员且默认<strong>已通过审核</strong>。
        </p>

        <div id="error-box" style="display:none;">
            @error('file')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <a class="btn btn-primary" href="{{ route('admin.users.import.template') }}">下载导入模板（xlsx）</a>
        </div>

        <form id="import-form" method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data" class="stack">
            @csrf

            <div>
                <label for="file">选择 Excel 文件（xlsx / xls / csv）</label>
                <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit" id="start-btn">开始导入</button>
                <a class="muted" href="{{ route('admin.users.index') }}">返回用户列表</a>
            </div>
        </form>
    </div>

    <div id="import-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
        <div class="card stack" style="min-width:400px;text-align:center;padding:32px;">
            <h3>正在导入，请稍候...</h3>
            <div style="width:100%;height:20px;background:#e9ecef;border-radius:10px;overflow:hidden;margin:16px 0;">
                <div id="progress-bar" style="width:0%;height:100%;background:#0d6efd;border-radius:10px;transition:width 0.3s;"></div>
            </div>
            <p class="muted" id="progress-text">准备中...</p>
            <button id="result-btn" class="btn btn-primary" style="display:none;margin-top:12px;">确定</button>
        </div>
    </div>

    <script>
    var overlay = document.getElementById('import-overlay');
    var progressBar = document.getElementById('progress-bar');
    var progressText = document.getElementById('progress-text');
    var startBtn = document.getElementById('start-btn');
    var fileInput = document.getElementById('file');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var resultBtn = document.getElementById('result-btn');
    var finished = false;

    overlay.style.display = 'none';

    function showResult(type, message) {
        progressBar.style.background = type === 'success' ? '#198754' : '#dc3545';
        progressText.textContent = message;
        progressText.style.color = type === 'success' ? '#198754' : '#dc3545';
        if (type === 'success') {
            resultBtn.style.display = '';
            resultBtn.onclick = function() {
                location.href = '{{ route('admin.users.index') }}';
            };
        }
    }

    document.getElementById('import-form').addEventListener('submit', function(e) {
        e.preventDefault();

        if (!fileInput.files.length) return;

        finished = false;

        startBtn.disabled = true;
        startBtn.textContent = '导入中...';

        overlay.style.display = 'flex';
        progressBar.style.width = '0%';
        progressBar.style.background = '#0d6efd';
        progressText.style.color = '';
        progressText.textContent = '正在准备导入...';

        var pollTimer = setInterval(function() {
            if (finished) return;
            fetch('{{ route('admin.import.progress') }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (finished) return;
                if (data.completed) {
                    finished = true;
                    clearInterval(pollTimer);
                    progressBar.style.width = '100%';
                    showResult('success', '导入完成，共 ' + data.total + ' 个用户。');
                } else if (data.total > 0) {
                    var pct = Math.round(data.current / data.total * 100);
                    progressBar.style.width = pct + '%';
                    progressText.textContent = '已导入 ' + data.current + ' / ' + data.total + ' 条';
                }
            });
        }, 800);

        var formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            finished = true;
            clearInterval(pollTimer);

            if (data.success) {
                progressBar.style.width = '100%';
                showResult('success', '导入完成，共 ' + data.total + ' 个用户。');
            } else {
                var errs = data.errors ? data.errors.file || data.errors : ['导入失败'];
                showResult('error', errs.join('<br>'));

                setTimeout(function() {
                    overlay.style.display = 'none';
                    startBtn.disabled = false;
                    startBtn.textContent = '开始导入';
                }, 3000);
            }
        })
        .catch(function() {
            finished = true;
            clearInterval(pollTimer);
            overlay.style.display = 'none';
            startBtn.disabled = false;
            startBtn.textContent = '开始导入';
            alert('导入请求失败，请重试。');
        });
    });
    </script>
@endsection