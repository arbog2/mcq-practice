@extends('layouts.app')

@section('title', '错题重练')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1>错题重练</h1>
            <p class="muted">从错题本中抽取题目重新练习，答对后自动标记为已掌握。</p>
            @error('error')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="card stack">
            <div class="row" style="justify-content:space-between;align-items:center;">
                <div>
                    <strong>全部错题</strong>
                    <span class="muted" style="margin-left:10px;">共 {{ $totalWrong }} 题</span>
                </div>
                @if($totalWrong > 0)
                    <form method="POST" action="{{ route('student.wrong-book.review.start') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">整体抽取</button>
                    </form>
                @else
                    <span class="pill">暂无错题</span>
                @endif
            </div>
        </div>

        @if($categories->isNotEmpty())
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>分类</th>
                            <th>错题数</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $cat)
                            <tr>
                                <td><strong>{{ $cat->name }}</strong></td>
                                <td>{{ $cat->wrong_count }}</td>
                                <td style="text-align:right;">
                                    @if($cat->wrong_count > 0)
                                        <form method="POST" action="{{ route('student.wrong-book.review.start') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="category_id" value="{{ $cat->id }}">
                                            <button class="btn btn-primary" type="submit">分类抽取</button>
                                        </form>
                                    @else
                                        <span class="pill">无错题</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div>
            <a class="btn" href="{{ route('student.wrong-book') }}">返回错题本</a>
        </div>
    </div>
@endsection