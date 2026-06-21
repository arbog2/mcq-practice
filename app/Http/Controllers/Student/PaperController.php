<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\PaperAttempt;
use App\Services\PaperService;
use Illuminate\Http\Request;

class PaperController extends Controller
{
    public function __construct(
        private PaperService $paperService
    ) {}

    public function index()
    {
        $papers = ExamPaper::query()
            ->where('is_active', true)
            ->withCount(['questions' => fn ($q) => $q->where('is_active', true)])
            ->orderByDesc('id')
            ->paginate(20);

        return view('student.papers.index', compact('papers'));
    }

    public function start(ExamPaper $examPaper)
    {
        if (! $examPaper->is_active) {
            abort(404);
        }

        try {
            $attempt = $this->paperService->startPaper($examPaper, auth()->id());

            return redirect()->route('student.papers.attempts.show', $attempt);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('student.papers.index')
                ->withErrors(['paper' => $e->getMessage()]);
        }
    }

    public function showAttempt(PaperAttempt $paperAttempt)
    {
        $this->authorizeAttempt($paperAttempt);

        if ($paperAttempt->status !== PaperAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.papers.attempts.result', $paperAttempt);
        }

        $paperAttempt->load(['questions.options']);
        $questions = $paperAttempt->questions;

        return view('student.papers.attempt', compact('paperAttempt', 'questions'));
    }

    public function submit(Request $request, PaperAttempt $paperAttempt)
    {
        $this->authorizeAttempt($paperAttempt);

        if ($paperAttempt->status !== PaperAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.papers.attempts.result', $paperAttempt);
        }

        $paperAttempt->load('questions.options');

        $rules = ['answers' => ['required', 'array']];
        foreach ($paperAttempt->questions as $question) {
            $rules['answers.'.$question->id] = ['nullable', 'integer', 'exists:question_options,id'];
        }

        $validated = $request->validate($rules);

        try {
            $this->paperService->submitPaper($paperAttempt, $validated['answers']);

            return redirect()->route('student.papers.attempts.result', $paperAttempt);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function result(PaperAttempt $paperAttempt)
    {
        $this->authorizeAttempt($paperAttempt);

        if ($paperAttempt->status !== PaperAttempt::STATUS_SUBMITTED) {
            return redirect()->route('student.papers.attempts.show', $paperAttempt);
        }

        $paperAttempt->load([
            'questions.options',
            'answers.selectedOption',
        ]);

        return view('student.papers.result', compact('paperAttempt'));
    }

    public function history()
    {
        $attempts = PaperAttempt::query()
            ->where('user_id', auth()->id())
            ->where('status', PaperAttempt::STATUS_SUBMITTED)
            ->with('paper')
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('student.papers.history', compact('attempts'));
    }

    private function authorizeAttempt(PaperAttempt $attempt): void
    {
        abort_if($attempt->user_id !== auth()->id(), 403);
    }
}
