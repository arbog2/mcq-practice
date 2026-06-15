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
    } else if (savedMode) {
        modeSelect.value = savedMode;
        switchMode(savedMode);
    } else {
        modeSelect.value = 'single';
        switchMode('single');
        localStorage.setItem('attempt-mode', 'single');
    }
})();
