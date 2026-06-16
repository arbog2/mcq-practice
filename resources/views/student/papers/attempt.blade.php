@extends('layouts.app')

@section('title', '试卷作答')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between;align-items:center;">
            <div>
                <h1 style="margin:0;">{{ $paperAttempt->paper?->title ?? '练习' }}</h1>
                <p class="muted" style="margin:4px 0 0;">共 {{ $questions->count() }} 题，选项顺序已打乱。</p>
            </div>
            <label class="row" style="gap:8px;align-items:center;">
                <span class="muted">模式</span>
                <select id="mode-select" style="width:auto;">
                    <option value="all">一页全显示</option>
                    <option value="single">一页一题</option>
                </select>
            </label>
        </div>

        <form method="POST" action="{{ route('student.papers.attempts.submit', $paperAttempt) }}" class="stack" id="attempt-form">
            @csrf

            <div id="questions-container">
                @php $qTotal = $questions->count(); @endphp

                @foreach ($questions as $index => $question)
                    @php
                        $shuffled = \App\Helpers\QuestionHelper::shuffledOptions($paperAttempt, $question);
                    @endphp
                    <div class="card stack question-card" data-index="{{ $index }}">
                        <div><span class="pill">第 {{ $index + 1 }} / {{ $qTotal }} 题</span> <span class="muted" style="font-size:0.85em;">{{ $question->score ?? 1 }} 分</span></div>
                        <div class="rich-text">{!! $question->stem !!}</div>

                        <div class="stack" style="gap:10px;">
                            @foreach ($shuffled as $i => $opt)
                                <label class="row" style="align-items:flex-start; gap:10px;">
                                    <input
                                        type="radio"
                                        name="answers[{{ $question->id }}]"
                                        value="{{ $opt->id }}"
                                        style="margin-top:3px;"
                                        {{ old('answers.'.$question->id) == $opt->id ? 'checked' : '' }}
                                    >
                                    <span class="rich-text" style="flex:1;"><strong>{{ ['A','B','C','D'][$i] }}.</strong> {!! $opt->content !!}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('answers.'.$question->id)
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div id="mobile-nav-top" style="display:none;">
                <div class="card row" style="justify-content:space-between;align-items:center;">
                    <button type="button" class="btn" id="m-prev-btn" style="visibility:hidden;">上一题</button>
                    <span class="muted" id="m-counter">1 / {{ $qTotal }}</span>
                    <button type="button" class="btn" id="m-next-btn">下一题</button>
                </div>
            </div>

            <div id="single-nav" style="display:none;">
                <div id="qgrid" class="question-grid">
                    @for($i = 0; $i < $qTotal; $i++)
                        <button type="button" class="qnum-btn unanswered" data-index="{{ $i }}">{{ $i + 1 }}</button>
                    @endfor
                </div>
                <div class="row" style="justify-content:space-between;align-items:center;margin-top:10px;">
                    <button type="button" class="btn" id="prev-btn" style="visibility:hidden;">上一题</button>
                    <span class="muted" id="q-counter">1 / {{ $qTotal }}</span>
                    <button type="button" class="btn" id="next-btn">下一题</button>
                </div>
            </div>

            <div id="single-bottom" style="display:none;">
                <div class="card row" style="justify-content:flex-start;align-items:center;">
                    <button class="btn btn-primary" type="submit">提交答卷</button>
                    <span class="muted">提交后将自动评分并展示解析。</span>
                </div>
            </div>

            <div id="mobile-bottom" style="display:none;">
                <button class="btn btn-primary" type="submit" style="width:100%;">提交答卷</button>
            </div>

            <div id="all-nav" class="card row">
                <button class="btn btn-primary" type="submit">提交答卷</button>
                <span class="muted">提交后将自动评分并展示解析。</span>
            </div>
        </form>
    </div>

    <script src="{{ asset('js/attempt.js') }}"></script>
@endsection
