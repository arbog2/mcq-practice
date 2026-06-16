<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExamPaper;
use App\Models\Log;
use App\Models\PaperAttempt;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamPaperController extends Controller
{
    public function index()
    {
        $papers = ExamPaper::query()
            ->withCount('questions')
            ->with('creator')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.papers.index', compact('papers'));
    }

    public function create()
    {
        return view('admin.papers.form', [
            'paper' => null,
            'action' => route('admin.papers.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $paper = ExamPaper::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'total_score' => 0,
            'created_by' => auth()->id(),
        ]);

        Log::record('创建试卷', 'question', '创建试卷 ID：'.$paper->id);

        return redirect()->route('admin.papers.questions', $paper)
            ->with('status', '试卷已创建，请添加题目。');
    }

    public function edit(ExamPaper $examPaper)
    {
        return view('admin.papers.form', [
            'paper' => $examPaper,
            'action' => route('admin.papers.update', $examPaper),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, ExamPaper $examPaper)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $examPaper->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Log::record('编辑试卷', 'question', '编辑试卷 ID：'.$examPaper->id);

        return redirect()->route('admin.papers.index')->with('status', '试卷已更新。');
    }

    public function destroy(ExamPaper $examPaper)
    {
        Log::record('删除试卷', 'question', '删除试卷 ID：'.$examPaper->id);
        $examPaper->delete();

        return redirect()->route('admin.papers.index')->with('status', '试卷已删除。');
    }

    public function questions(ExamPaper $examPaper)
    {
        $examPaper->load(['questions' => fn ($q) => $q->orderByPivot('display_order')]);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedQuestionIds = $examPaper->questions->pluck('id')->toArray();

        return view('admin.papers.questions', compact('examPaper', 'categories', 'selectedQuestionIds'));
    }

    public function questionsSearch(Request $request, ExamPaper $examPaper)
    {
        $categoryId = $request->query('category_id');
        $keyword = $request->query('keyword');

        $query = Question::query()
            ->where('is_active', true)
            ->with('category')
            ->orderByDesc('id');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($keyword) {
            $query->where('stem', 'like', '%'.addcslashes($keyword, '%_').'%');
        }

        $questions = $query->paginate(20)->withQueryString();
        $selectedIds = $examPaper->questions()->pluck('question_id')->toArray();

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.papers.partials.question-list', compact('questions', 'selectedIds'))->render(),
                'pagination' => $questions->links()->toHtml(),
            ]);
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.papers.questions-search', compact('examPaper', 'questions', 'categories', 'selectedIds'));
    }

    public function questionsStore(Request $request, ExamPaper $examPaper)
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['exists:questions,id'],
        ]);

        $maxOrder = $examPaper->questions()->max('display_order') ?? 0;
        $added = 0;
        $totalScore = 0;

        DB::transaction(function () use ($examPaper, $validated, &$added, &$totalScore, &$maxOrder) {
            foreach ($validated['question_ids'] as $id) {
                $exists = $examPaper->questions()->where('question_id', $id)->exists();
                if (! $exists) {
                    $maxOrder++;
                    $examPaper->questions()->attach($id, ['display_order' => $maxOrder]);
                    $added++;
                }
            }

            $totalScore = $examPaper->questions()->sum('score');
            $examPaper->update(['total_score' => $totalScore]);
        });

        Log::record('组卷添加题目', 'question', "试卷 ID：{$examPaper->id} 添加 {$added} 题");

        return response()->json([
            'message' => "已添加 {$added} 道题目。",
            'added' => $added,
            'total_score' => $totalScore,
            'question_count' => $examPaper->questions()->count(),
        ]);
    }

    public function questionsDestroy(ExamPaper $examPaper, Question $question)
    {
        $examPaper->questions()->detach($question->id);

        $totalScore = $examPaper->questions()->sum('score');
        $examPaper->update(['total_score' => $totalScore]);

        return response()->json([
            'message' => '题目已移除。',
            'total_score' => $totalScore,
            'question_count' => $examPaper->questions()->count(),
        ]);
    }

    public function questionsReorder(Request $request, ExamPaper $examPaper)
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        DB::transaction(function () use ($examPaper, $validated) {
            foreach ($validated['question_ids'] as $order => $questionId) {
                $examPaper->questions()->updateExistingPivot($questionId, [
                    'display_order' => $order + 1,
                ]);
            }
        });

        return response()->json(['message' => '排序已更新。']);
    }

    public function stats(ExamPaper $examPaper)
    {
        $examPaper->loadCount('questions');

        $attempts = PaperAttempt::query()
            ->selectRaw('
                user_id,
                MAX(id) as id,
                MAX(score) as score,
                MAX(total_score) as total_score,
                MAX(correct_count) as correct_count,
                MAX(question_count) as question_count,
                MAX(submitted_at) as submitted_at,
                TIMESTAMPDIFF(SECOND, MIN(started_at), MAX(submitted_at)) as duration_seconds
            ')
            ->where('exam_paper_id', $examPaper->id)
            ->where('status', PaperAttempt::STATUS_SUBMITTED)
            ->groupBy('user_id')
            ->with('user.organizationUnit.parent')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        return view('admin.papers.stats', compact('examPaper', 'attempts'));
    }

}
