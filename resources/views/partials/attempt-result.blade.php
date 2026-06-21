@php
    $answersByQuestionId = $attempt->answers->keyBy('question_id');
@endphp

<div class="card stack">
    <h1>{{ $title }}</h1>
    <p class="muted">
        @if($isAdmin)
            学员：<strong>{{ $attempt->user?->name ?? '—' }}</strong>
            · 
        @endif
        得分：<strong>{{ $attempt->score }}</strong> / {{ $attempt->total_score }} 分（正确 {{ $attempt->correct_count }} / {{ $attempt->question_count }}）
    </p>
    <div class="row">
        <a class="btn btn-primary" href="{{ $backRoute }}">{{ $backText ?? '← 返回' }}</a>
        @if(isset($secondaryRoute) && isset($secondaryText))
            <a class="btn" href="{{ $secondaryRoute }}">{{ $secondaryText }}</a>
        @endif
    </div>
</div>

@foreach ($attempt->questions as $index => $question)
    @php
        $answer = $answersByQuestionId->get($question->id);
        $selected = $answer?->selectedOption;
        $correct = $question->options->firstWhere('is_correct', true);
        $shuffled = \App\Helpers\QuestionHelper::shuffledOptions($attempt, $question);
        $selectedLabel = \App\Helpers\QuestionHelper::labelForOption($shuffled, $selected?->id);
        $correctLabel = \App\Helpers\QuestionHelper::labelForOption($shuffled, $correct?->id);
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
                <div><span class="rich-text"><strong>{{ ['A','B','C','D'][$i] }}.</strong> {!! $opt->content !!}</span></div>
            @endforeach
        </div>

        <div class="muted">
            {{ $isAdmin ? '学员选择' : '你的选择' }}：
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
