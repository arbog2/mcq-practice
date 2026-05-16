<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\UserWrongQuestion;
use App\Services\PracticeService;
use Illuminate\Http\Request;

class WrongBookController extends Controller
{
    public function __construct(
        private PracticeService $practiceService
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

        return redirect()->back()->with('status', __('已标记为掌握。'));
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
            ->get()
            ->map(function ($category) use ($userId) {
                $category->wrong_count = UserWrongQuestion::where('user_id', $userId)
                    ->where('category_id', $category->id)
                    ->whereNull('mastered_at')
                    ->count();
                return $category;
            });

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
        $categoryId = $validated['category_id'] ?? $wrongs->first()->category_id;

        $attempt = $this->practiceService->startPracticeWithQuestions($categoryId, $userId, $questionIds);

        return redirect()->route('student.attempts.show', $attempt);
    }
}
