@extends('layouts.app')

@section('title', '试卷作答')

@section('content')
    @include('partials.attempt-form', [
        'paperAttempt' => $paperAttempt,
        'questions' => $questions,
        'title' => $paperAttempt->paper?->title ?? '练习',
        'submitRoute' => route('student.papers.attempts.submit', $paperAttempt),
    ])
@endsection
