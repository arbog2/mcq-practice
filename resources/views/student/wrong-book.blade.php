@extends('layouts.app')

@section('title', '错题本')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1>错题本</h1>
            <p class="muted">按分类筛选错题；标记“已掌握”后将从列表隐藏（不影响后台统计）。</p>

            <div class="row" style="justify-content:space-between;">
                <form method="GET" action="{{ route('student.wrong-book') }}" class="row">
                    <label class="row" style="gap:10px; align-items:center;">
                        <span class="muted">分类</span>
                        <select name="category_id" onchange="this.form.submit()">
                            <option value="">全部</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected((string)$categoryId === (string)$c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>
                <a class="btn btn-primary" href="{{ route('student.wrong-book.review') }}">错题重练</a>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>题目</th>
                        <th>分类</th>
                        <th>错次</th>
                        <th>最近错误</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($wrongs as $row)
                        <tr>
                            <td style="max-width:520px;">{{ \Illuminate\Support\Str::limit(strip_tags($row->question->stem), 120) }}</td>
                            <td>{{ $row->category->name }}</td>
                            <td>{{ $row->wrong_count }}</td>
                            <td class="muted">{{ optional($row->last_wrong_at)->format('Y-m-d H:i') }}</td>
                            <td style="text-align:right;">
                                <form method="POST" action="{{ route('student.wrong-book.master', $row) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit">标记已掌握</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">暂无错题记录。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="muted">
            {{ $wrongs->links() }}
        </div>
    </div>
@endsection
