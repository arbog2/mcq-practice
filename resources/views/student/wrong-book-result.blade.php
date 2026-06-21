@extends('layouts.app')

@section('title', '错题重练结果')

@section('content')
    <div class="stack">
        @include('partials.attempt-result', [
            'attempt' => $paperAttempt,
            'title' => '错题重练 — 结果',
            'backRoute' => route('student.wrong-book.review'),
            'backText' => '继续重练',
            'isAdmin' => false,
            'secondaryRoute' => route('student.wrong-book'),
            'secondaryText' => '返回错题本',
        ])
    </div>
@endsection
