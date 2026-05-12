@extends('layouts.app')

@section('title', '练习结果')

@section('content')
    @php
        $answersByQuestionId = $attempt->answers->keyBy('question_id');
    @endphp

    <div class="stack">
        <div class="card stack">
            <h1>练习结果</h1>
            <p class="muted">
                分类：{{ $attempt->category->name }}；
                得分：<strong>{{ $attempt->score }}</strong> 分（正确 {{ $attempt->correct_count }} / {{ $attempt->question_count }}）
            </p>
            <div class="row">
                <a class="btn btn-primary" href="{{ route('student.categories') }}">继续练习</a>
                <a class="btn" href="{{ route('student.wrong-book') }}">查看错题本</a>
            </div>
        </div>

        @foreach ($attempt->questions as $index => $question)
            @php
                $answer = $answersByQuestionId->get($question->id);
                $selected = $answer?->selectedOption;
                $correct = $question->options->firstWhere('is_correct', true);
                $seed = crc32($attempt->id.'-'.$question->id);
                $shuffled = $question->options->shuffle($seed)->values();
                $labels = ['A', 'B', 'C', 'D'];
                $selectedLabel = null;
                $correctLabel = null;
                foreach ($shuffled as $i => $opt) {
                    if ($selected && $opt->id === $selected->id) $selectedLabel = $labels[$i];
                    if ($correct && $opt->id === $correct->id) $correctLabel = $labels[$i];
                }
            @endphp

            <div class="card stack">
                <div class="row" style="justify-content:space-between;">
                    <div><span class="pill">第 {{ $index + 1 }} 题</span></div>
                    <div>
                        @if($answer && $answer->is_correct)
                            <span class="pill" style="border-color:#bbf7d0; color:#166534;">正确</span>
                        @else
                            <span class="pill" style="border-color:#fecaca; color:#991b1b;">错误</span>
                        @endif
                    </div>
                </div>

                <div class="rich-text">{!! $question->stem !!}</div>

                <div class="stack" style="gap:8px; margin:8px 0;">
                    @foreach ($shuffled as $i => $opt)
                        <div><span class="rich-text"><strong>{{ $labels[$i] }}.</strong> {!! $opt->content !!}</span></div>
                    @endforeach
                </div>

                <div class="muted">
                    你的选择：
                    @if($selected)
                        <span class="rich-text"><strong>{{ $selectedLabel }}.</strong> {!! $selected->content !!}</span>
                    @else
                        <strong>未作答</strong>
                    @endif
                </div>

                <div class="muted">
                    正确答案：
                    @if($correct)
                        <span class="rich-text"><strong>{{ $correctLabel }}.</strong> {!! $correct->content !!}</span>
                    @else
                        —
                    @endif
                </div>

                @if($question->explanation)
                    <div class="card stack" style="background:#fafafa;">
                        <div class="muted">解析</div>
                        <div class="rich-text">{!! $question->explanation !!}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
