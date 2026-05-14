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
                    @php
                        $seed = crc32($attempt->id.'-'.$question->id);
                        $shuffled = $question->options->shuffle($seed)->values();
                        $labels = ['A', 'B', 'C', 'D'];
                    @endphp
                    <div class="card stack question-card" data-index="{{ $index }}">
                        <div><span class="pill">第 {{ $index + 1 }} / {{ $qTotal }} 题</span></div>
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
                                    <span class="rich-text" style="flex:1;"><strong>{{ $labels[$i] }}.</strong> {!! $opt->content !!}</span>
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
        var mobileNavTop = document.getElementById('mobile-nav-top');
        var mobileBottom = document.getElementById('mobile-bottom');
        var mPrevBtn = document.getElementById('m-prev-btn');
        var mNextBtn = document.getElementById('m-next-btn');
        var mCounter = document.getElementById('m-counter');
        var currentIndex = 0;
        var total = qCards.length;
        var isMobile = window.innerWidth < 768;

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
            if (qGrid) {
                var btns = qGrid.querySelectorAll('.qnum-btn');
                btns.forEach(function(btn, i) {
                    btn.className = 'qnum-btn' + (answered[i] ? ' answered' : ' unanswered') + (i === currentIndex ? ' active' : '');
                });
            }
        }

        function showQuestion(index) {
            qCards.forEach(function(card, i) {
                card.style.display = i === index ? '' : 'none';
            });
            currentIndex = index;
            var text = (index + 1) + ' / ' + total;
            if (qCounter) qCounter.textContent = text;
            if (mCounter) mCounter.textContent = text;
            var hidden = index === 0 ? 'hidden' : 'visible';
            var hiddenEnd = index === total - 1 ? 'hidden' : 'visible';
            if (prevBtn) prevBtn.style.visibility = hidden;
            if (nextBtn) nextBtn.style.visibility = hiddenEnd;
            if (mPrevBtn) mPrevBtn.style.visibility = hidden;
            if (mNextBtn) mNextBtn.style.visibility = hiddenEnd;
            updateGrid();
        }

        function switchMode(mode) {
            var form = document.getElementById('attempt-form');
            if (mode === 'mobile') {
                qCards.forEach(function(card, i) {
                    card.style.display = i === currentIndex ? '' : 'none';
                });
                mobileNavTop.style.display = '';
                mobileBottom.style.display = '';
                singleNav.style.display = 'none';
                singleBottom.style.display = 'none';
                allNav.style.display = 'none';
                form.classList.remove('single-mode-form');
                form.classList.add('mobile-mode-form');
                updateGrid();
            } else if (mode === 'single') {
                qCards.forEach(function(card, i) {
                    card.style.display = i === currentIndex ? '' : 'none';
                });
                singleNav.style.display = '';
                singleBottom.style.display = '';
                allNav.style.display = 'none';
                mobileNavTop.style.display = 'none';
                mobileBottom.style.display = 'none';
                form.classList.remove('mobile-mode-form');
                form.classList.add('single-mode-form');
                updateGrid();
            } else {
                qCards.forEach(function(card) { card.style.display = ''; });
                singleNav.style.display = 'none';
                singleBottom.style.display = 'none';
                allNav.style.display = '';
                mobileNavTop.style.display = 'none';
                mobileBottom.style.display = 'none';
                form.classList.remove('single-mode-form', 'mobile-mode-form');
            }
        }

        function goPrev() { if (currentIndex > 0) showQuestion(currentIndex - 1); }
        function goNext() { if (currentIndex < total - 1) showQuestion(currentIndex + 1); }

        function detectAndSwitch() {
            var wasMobile = isMobile;
            isMobile = window.innerWidth < 768;
            if (isMobile) {
                modeSelect.value = 'mobile';
                switchMode('mobile');
            } else if (wasMobile) {
                var saved = localStorage.getItem('attempt-mode');
                modeSelect.value = saved === 'single' ? 'single' : 'all';
                switchMode(modeSelect.value);
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

        if (prevBtn) prevBtn.addEventListener('click', goPrev);
        if (nextBtn) nextBtn.addEventListener('click', goNext);
        if (mPrevBtn) mPrevBtn.addEventListener('click', goPrev);
        if (mNextBtn) mNextBtn.addEventListener('click', goNext);

        document.getElementById('attempt-form').addEventListener('change', function(e) {
            if (e.target && e.target.type === 'radio') updateGrid();
        });

        // Touch swipe
        var touchStartX = 0;
        var touchEndX = 0;
        var formEl = document.getElementById('attempt-form');
        formEl.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        formEl.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            var diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) goNext(); else goPrev();
            }
        }, { passive: true });

        modeSelect.addEventListener('change', function() {
            if (window.innerWidth < 768) return;
            if (this.value !== 'mobile') {
                switchMode(this.value);
                localStorage.setItem('attempt-mode', this.value);
            }
        });

        window.addEventListener('resize', detectAndSwitch);

        var savedMode = localStorage.getItem('attempt-mode');
        if (window.innerWidth < 768) {
            modeSelect.value = 'mobile';
            switchMode('mobile');
        } else if (savedMode === 'single') {
            modeSelect.value = 'single';
            switchMode('single');
        } else {
            switchMode('all');
        }
    })();
    </script>
@endsection