<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PaperAttempt;
use App\Models\Setting;
use App\Models\UserWrongQuestion;
use App\Services\PaperService;
use Illuminate\Http\Request;

class WrongBookController extends Controller
{
    public function __construct(
        private PaperService $paperService
    ) {}

    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');

        $query = UserWrongQuestion::query()
            ->where('user_id', auth()->id())
            ->whereNull('mastered_at')
            ->with(['question.options', 'category']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $wrongs = $query
            ->orderByDesc('last_wrong_at')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('student.wrong-book', compact('wrongs', 'categories', 'categoryId'));
    }

    public function master(UserWrongQuestion $userWrongQuestion)
    {
        abort_if($userWrongQuestion->user_id !== auth()->id(), 403);

        $userWrongQuestion->update(['mastered_at' => now()]);

        return redirect()->back()->with('status', '已标记为掌握。');
    }

    public function reviewForm()
    {
        $userId = auth()->id();

        $totalWrong = UserWrongQuestion::where('user_id', $userId)
            ->whereNull('mastered_at')
            ->count();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereIn('id', function ($q) use ($userId) {
                $q->select('category_id')
                    ->from('user_wrong_questions')
                    ->where('user_id', $userId)
                    ->whereNull('mastered_at');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount(['wrongQuestions' => function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereNull('mastered_at');
            }])
            ->get();

        return view('student.wrong-book-review', compact('totalWrong', 'categories'));
    }

    public function startReview(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $count = max(1, (int) Setting::get('questions_per_session', config('practice.questions_per_session')));
        $userId = auth()->id();

        $query = UserWrongQuestion::where('user_id', $userId)
            ->whereNull('mastered_at')
            ->inRandomOrder();

        if (! empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        $wrongs = $query->limit($count)->get();

        if ($wrongs->isEmpty()) {
            return redirect()->route('student.wrong-book.review')
                ->withErrors(['error' => '没有可用的错题。']);
        }

        $questionIds = $wrongs->pluck('question_id');

        $attempt = $this->paperService->startWrongBookReview($userId, $questionIds);

        return redirect()->route('student.wrong-book.attempt.show', $attempt);
    }

    public function showAttempt(PaperAttempt $paperAttempt)
    {
        $this->authorizeAttempt($paperAttempt);

        if ($paperAttempt->status !== PaperAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.wrong-book.attempt.result', $paperAttempt);
        }

        $paperAttempt->load('questions.options');
        $questions = $paperAttempt->questions;

        return view('student.wrong-book-attempt', compact('paperAttempt', 'questions'));
    }

    public function submit(Request $request, PaperAttempt $paperAttempt)
    {
        $this->authorizeAttempt($paperAttempt);

        if ($paperAttempt->status !== PaperAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.wrong-book.attempt.result', $paperAttempt);
        }

        $paperAttempt->load('questions.options');

        $rules = ['answers' => ['required', 'array']];
        foreach ($paperAttempt->questions as $question) {
            $rules['answers.'.$question->id] = ['nullable', 'integer', 'exists:question_options,id'];
        }

        $validated = $request->validate($rules);

        try {
            $this->paperService->submitPaper($paperAttempt, $validated['answers']);

            return redirect()->route('student.wrong-book.attempt.result', $paperAttempt);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function result(PaperAttempt $paperAttempt)
    {
        $this->authorizeAttempt($paperAttempt);

        if ($paperAttempt->status !== PaperAttempt::STATUS_SUBMITTED) {
            return redirect()->route('student.wrong-book.attempt.show', $paperAttempt);
        }

        $paperAttempt->load([
            'questions.options',
            'answers.selectedOption',
        ]);

        return view('student.wrong-book-result', compact('paperAttempt'));
    }

    private function authorizeAttempt(PaperAttempt $attempt): void
    {
        abort_if($attempt->user_id !== auth()->id(), 403);
    }
}
