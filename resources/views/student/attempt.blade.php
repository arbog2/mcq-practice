@extends('layouts.app')

@section('title', '练习作答')

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between;align-items:center;">
            <div>
                <h1 style="margin:0;">练习：{{ $attempt->category->name }}</h1>
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

        <form method="POST" action="{{ route('student.attempts.submit', $attempt) }}" class="stack" id="attempt-form">
            @csrf

            <div id="questions-container">
                @php $qTotal = $questions->count(); @endphp

                @foreach ($questions as $index => $question)
                    @php($opts = $question->options->shuffle())
                    <div class="card stack question-card" data-index="{{ $index }}">
                        <div><span class="pill">第 {{ $index + 1 }} / {{ $qTotal }} 题</span></div>
                        <div class="rich-text">{!! $question->stem !!}</div>

                        <div class="stack" style="gap:10px;">
                            @foreach ($opts as $opt)
                                <label class="row" style="align-items:flex-start; gap:10px;">
                                    <input
                                        type="radio"
                                        name="answers[{{ $question->id }}]"
                                        value="{{ $opt->id }}"
                                        style="margin-top:3px;"
                                        {{ old('answers.'.$question->id) == $opt->id ? 'checked' : '' }}
                                    >
                                    <span class="rich-text" style="flex:1;"><strong>{{ $opt->label }}.</strong> {!! $opt->content !!}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('answers.'.$question->id)
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
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

            <div id="all-nav" class="card row">
                <button class="btn btn-primary" type="submit">提交答卷</button>
                <span class="muted">提交后将自动评分并展示解析。</span>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var modeSelect = document.getElementById('mode-select');
        var qCards = document.querySelectorAll('.question-card');
        var qGrid = document.getElementById('qgrid');
        var singleNav = document.getElementById('single-nav');
        var singleBottom = document.getElementById('single-bottom');
        var allNav = document.getElementById('all-nav');
        var prevBtn = document.getElementById('prev-btn');
        var nextBtn = document.getElementById('next-btn');
        var qCounter = document.getElementById('q-counter');
        var currentIndex = 0;
        var total = qCards.length;

        function getAnswered() {
            var answered = {};
            qCards.forEach(function(card, i) {
                var checked = card.querySelector('input[type="radio"]:checked');
                answered[i] = !!checked;
            });
            return answered;
        }

        function updateGrid() {
            var answered = getAnswered();
            var btns = qGrid.querySelectorAll('.qnum-btn');
            btns.forEach(function(btn, i) {
                btn.className = 'qnum-btn' + (answered[i] ? ' answered' : ' unanswered') + (i === currentIndex ? ' active' : '');
            });
        }

        function showQuestion(index) {
            qCards.forEach(function(card, i) {
                card.style.display = i === index ? '' : 'none';
            });
            currentIndex = index;
            qCounter.textContent = (index + 1) + ' / ' + total;
            prevBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
            nextBtn.style.visibility = index === total - 1 ? 'hidden' : 'visible';
            updateGrid();
        }

        function switchMode(mode) {
            var form = document.getElementById('attempt-form');
            if (mode === 'single') {
                qCards.forEach(function(card, i) {
                    card.style.display = i === currentIndex ? '' : 'none';
                });
                singleNav.style.display = '';
                singleBottom.style.display = '';
                allNav.style.display = 'none';
                form.classList.add('single-mode-form');
                updateGrid();
            } else {
                qCards.forEach(function(card) { card.style.display = ''; });
                singleNav.style.display = 'none';
                singleBottom.style.display = 'none';
                allNav.style.display = '';
                form.classList.remove('single-mode-form');
            }
        }

        if (qGrid) {
            qGrid.addEventListener('click', function(e) {
                var btn = e.target.closest('.qnum-btn');
                if (btn && modeSelect.value === 'single') {
                    showQuestion(parseInt(btn.getAttribute('data-index')));
                }
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentIndex > 0) showQuestion(currentIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentIndex < total - 1) showQuestion(currentIndex + 1);
            });
        }

        document.getElementById('attempt-form').addEventListener('change', function(e) {
            if (e.target && e.target.type === 'radio') {
                updateGrid();
            }
        });

        modeSelect.addEventListener('change', function() {
            switchMode(this.value);
        });

        // Save mode preference
        var savedMode = localStorage.getItem('attempt-mode');
        if (savedMode === 'single') {
            modeSelect.value = 'single';
            switchMode('single');
        } else {
            switchMode('all');
        }

        modeSelect.addEventListener('change', function() {
            localStorage.setItem('attempt-mode', this.value);
        });
    })();
    </script>
@endsection