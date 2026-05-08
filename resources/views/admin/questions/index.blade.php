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

        <div class="card">
            <table>
                <thead>
                    <tr>
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
                            <td>{{ $question->id }}</td>
                            <td>{{ $question->category->name }}</td>
                            <td style="max-width:400px;">{{ \Illuminate\Support\Str::limit($question->stem, 80) }}</td>
                            <td>{{ $question->is_active ? '是' : '否' }}</td>
                            <td style="text-align:right;">
                                <button class="btn" onclick="openAjaxModal('{{ route('admin.questions.edit', $question) }}', '编辑题目')">编辑</button>
                                <button class="btn btn-danger" onclick="deleteQuestion({{ $question->id }})">删除</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $questions->links() }}</div>
    </div>

    <div id="ajax-modal" class="modal">
        <div class="modal-backdrop" onclick="closeAjaxModal()"></div>
        <div class="modal-content">
            <div class="modal-header"><h3 id="ajax-modal-title"></h3><button class="modal-close" onclick="closeAjaxModal()">&times;</button></div>
            <div class="modal-body" id="ajax-modal-body"></div>
        </div>
    </div>
@endsection