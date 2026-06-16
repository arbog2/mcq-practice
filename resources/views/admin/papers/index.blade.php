@extends('layouts.app')

@section('title', '试卷管理')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between;align-items:center;">
            <h1 style="margin:0;">试卷管理</h1>
            <a class="btn btn-primary" href="{{ route('admin.papers.create') }}">新建试卷</a>
        </div>

        @if(session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>试卷名称</th>
                        <th>题目数</th>
                        <th>总分</th>
                        <th>创建者</th>
                        <th>启用</th>
                        <th>创建时间</th>
                        <th style="text-align:right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($papers as $paper)
                        <tr>
                            <td>{{ $paper->id }}</td>
                            <td><strong>{{ $paper->title }}</strong></td>
                            <td>{{ $paper->questions_count }}</td>
                            <td>{{ $paper->total_score }}</td>
                            <td>{{ $paper->creator?->name ?? '—' }}</td>
                            <td>{{ $paper->is_active ? '是' : '否' }}</td>
                            <td>{{ $paper->created_at?->format('Y-m-d') }}</td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a class="btn" href="{{ route('admin.papers.questions', $paper) }}" style="font-size:12px;padding:4px 8px;">组卷</a>
                                <a class="btn" href="{{ route('admin.papers.stats', $paper) }}" style="font-size:12px;padding:4px 8px;">统计</a>
                                <a class="btn" href="{{ route('admin.papers.edit', $paper) }}" style="font-size:12px;padding:4px 8px;">编辑</a>
                                <form method="POST" action="{{ route('admin.papers.destroy', $paper) }}" style="display:inline;" onsubmit="return confirm('确认删除试卷「{{ $paper->title }}」？')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" style="font-size:12px;padding:4px 8px;">删除</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $papers->links() }}</div>
    </div>
@endsection
