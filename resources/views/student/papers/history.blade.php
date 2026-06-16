@extends('layouts.app')

@section('title', '练习历史')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:center;">
            <h1 style="margin:0;">练习历史</h1>
            <a class="btn btn-primary" href="{{ route('student.papers.index') }}">试卷练习</a>
            <a class="btn" href="{{ route('student.wrong-book') }}">错题本</a>
        </div>

        @if ($attempts->isEmpty())
            <div class="card muted">暂无练习记录。</div>
        @else
            @foreach ($attempts as $attempt)
                <div class="card row" style="justify-content:space-between; align-items:center;">
                    <div class="stack" style="gap:4px;">
                        <strong>
                            @if($attempt->source === 'wrong_book')
                                错题重练
                            @else
                                {{ $attempt->paper?->title ?? '已删除试卷' }}
                            @endif
                        </strong>
                        <span class="muted">
                            @if($attempt->source === 'wrong_book')
                                <span class="pill" style="border-color:#fde68a;">错题重练</span>
                            @endif
                            {{ $attempt->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                             得分 {{ $attempt->score }}/{{ $attempt->total_score }}（正确 {{ $attempt->correct_count }}/{{ $attempt->question_count }}）
                        </span>
                    </div>
                    @if($attempt->source === 'wrong_book')
                        <a class="btn" href="{{ route('student.wrong-book.attempt.result', $attempt) }}">查看</a>
                    @else
                        <a class="btn" href="{{ route('student.papers.attempts.result', $attempt) }}">查看</a>
                    @endif
                </div>
            @endforeach

            <div class="muted">{{ $attempts->links() }}</div>
        @endif
    </div>
@endsection
