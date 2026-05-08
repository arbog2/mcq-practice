<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PracticeAttempt;
use App\Services\PracticeService;
use Illuminate\Http\Request;

class PracticeController extends Controller
{
    public function categories()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['questions' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('student.categories', compact('categories'));
    }

    public function __construct(
        private PracticeService $practiceService
    ) {}

    public function start(Category $category)
    {
        if (! $category->is_active) {
            abort(404);
        }

        try {
            $attempt = $this->practiceService->startPractice($category, auth()->id());
            return redirect()->route('student.attempts.show', $attempt);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('student.categories')
                ->withErrors(['category' => __($e->getMessage())]);
        }
    }

    public function showAttempt(PracticeAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->status !== PracticeAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.attempts.result', $attempt);
        }

        $attempt->load(['category', 'questions.options']);

        $questions = $attempt->questions;

        return view('student.attempt', compact('attempt', 'questions'));
    }

    public function submit(Request $request, PracticeAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->status !== PracticeAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.attempts.result', $attempt);
        }

        $attempt->load('questions.options');

        $rules = [
            'answers' => ['required', 'array'],
        ];

        foreach ($attempt->questions as $question) {
            $rules['answers.'.$question->id] = ['nullable', 'integer', 'exists:question_options,id'];
        }

        $validated = $request->validate($rules);

        try {
            $this->practiceService->submitPractice($attempt, $validated['answers']);
            return redirect()->route('student.attempts.result', $attempt);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function result(PracticeAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->status !== PracticeAttempt::STATUS_SUBMITTED) {
            return redirect()->route('student.attempts.show', $attempt);
        }

        $attempt->load([
            'category',
            'questions.options',
            'answers.selectedOption',
        ]);

        return view('student.result', compact('attempt'));
    }

    public function history()
    {
        $attempts = PracticeAttempt::query()
            ->where('user_id', auth()->id())
            ->where('status', PracticeAttempt::STATUS_SUBMITTED)
            ->with('category')
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('student.history', compact('attempts'));
    }

    private function authorizeAttempt(PracticeAttempt $attempt): void
    {
        abort_if($attempt->user_id !== auth()->id(), 403);
    }
}
