@extends('layouts.app')

@section('title', '试卷结果')

@section('content')
    <div class="stack">
        @include('partials.attempt-result', [
            'attempt' => $paperAttempt,
            'title' => ($paperAttempt->paper?->title ?? '练习') . ' — 结果',
            'backRoute' => route('student.papers.index'),
            'backText' => '返回试卷列表',
            'isAdmin' => false,
            'secondaryRoute' => route('student.papers.history'),
            'secondaryText' => '练习历史',
        ])
    </div>
@endsection
