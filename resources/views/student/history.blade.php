@extends('layouts.app')

@section('title', '练习历史')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between; align-items:center;">
            <h1 style="margin:0;">练习历史</h1>
            <a class="btn btn-primary" href="{{ route('student.categories') }}">开始练习</a>
        </div>

        @if ($attempts->isEmpty())
            <div class="card muted">暂无练习记录。</div>
        @else
            @foreach ($attempts as $attempt)
                <div class="card row" style="justify-content:space-between; align-items:center;">
                    <div class="stack" style="gap:4px;">
                        <strong>{{ $attempt->category->name ?? '未分类' }}</strong>
                        <span class="muted">
                            {{ $attempt->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                             得分 {{ $attempt->score }}/{{ $attempt->total_score }}（正确 {{ $attempt->correct_count }}/{{ $attempt->question_count }}）
                        </span>
                    </div>
                    <a class="btn" href="{{ route('student.attempts.result', $attempt) }}">查看</a>
                </div>
            @endforeach

            <div class="muted">{{ $attempts->links() }}</div>
        @endif
    </div>
@endsection
