@extends('layouts.app')

@section('title', '学员答卷 - ' . ($paperAttempt->paper?->title ?? '练习'))

@section('content')
    <div class="stack">
        @include('partials.attempt-result', [
            'attempt' => $paperAttempt,
            'title' => ($paperAttempt->paper?->title ?? '练习') . ' — 学员答卷',
            'backRoute' => route('admin.papers.stats', $paperAttempt->exam_paper_id),
            'backText' => '← 返回成绩统计',
            'isAdmin' => true,
            'secondaryRoute' => route('admin.papers.index'),
            'secondaryText' => '试卷列表',
        ])
    </div>
@endsection
