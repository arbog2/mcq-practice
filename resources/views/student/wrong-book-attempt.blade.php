@extends('layouts.app')

@section('title', '错题重练')

@section('content')
    @include('partials.attempt-form', [
        'paperAttempt' => $paperAttempt,
        'questions' => $questions,
        'title' => '错题重练',
        'submitRoute' => route('student.wrong-book.attempt.submit', $paperAttempt),
    ])
@endsection
