<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\PaperAttempt;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaperController extends Controller
{
    public function index()
    {
        $papers = ExamPaper::query()
            ->with('creator')
            ->withCount('questions')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.papers.index', compact('papers'));
    }

    public function create()
    {
        $paper = null;
        $action = route('admin.papers.store');
        $method = 'POST';

        return view('admin.papers.form', compact('paper', 'action', 'method'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);

        $paper = ExamPaper::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);

        return redirect()->route('admin.papers.questions', $paper)
            ->with('status', '试卷已创建，请添加题目。');
    }

    public function edit(ExamPaper $paper)
    {
        $action = route('admin.papers.update', $paper);
        $method = 'PUT';

        return view('admin.papers.form', compact('paper', 'action', 'method'));
    }

    public function update(Request $request, ExamPaper $paper)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paper->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('admin.papers.index')
            ->with('status', '试卷已更新。');
    }

    public function destroy(ExamPaper $paper)
    {
        $attemptCount = $paper->attempts()->count();

        if ($attemptCount > 0) {
            $paper->delete();
        } else {
            $paper->forceDelete();
        }

        return redirect()->route('admin.papers.index')
            ->with('status', '试卷已删除。');
    }

    public function questions(ExamPaper $paper)
    {
        $examPaper = $paper->load('questions.category');
        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.papers.questions', compact('examPaper', 'categories'));
    }

    public function searchQuestions(Request $request, ExamPaper $paper)
    {
        $query = Question::query()
            ->with('category')
            ->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('stem', 'like', "%{$keyword}%")
                  ->orWhereHas('options', function ($o) use ($keyword) {
                      $o->where('content', 'like', "%{$keyword}%");
                  });
            });
        }

        $questions = $query->orderBy('id')->paginate(20);
        $selectedIds = $paper->questions()->pluck('question_id')->toArray();

        $html = view('admin.papers.partials.question-list', compact('questions', 'selectedIds'))->render();
        $pagination = $questions->lastPage() > 1 ? $questions->links()->toHtml() : '';

        return response()->json(compact('html', 'pagination'));
    }

    public function addQuestions(Request $request, ExamPaper $paper)
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $maxOrder = $paper->questions()->max('display_order') ?? 0;

        $attachData = [];
        foreach ($validated['question_ids'] as $id) {
            if (!$paper->questions()->where('question_id', $id)->exists()) {
                $maxOrder++;
                $attachData[$id] = ['display_order' => $maxOrder];
            }
        }

        if (!empty($attachData)) {
            DB::transaction(function () use ($paper, $attachData) {
                $paper->questions()->attach($attachData);
                $paper->update([
                    'total_score' => $paper->questions()->sum('score'),
                ]);
            });
        }

        return response()->json(['success' => true]);
    }

    public function removeQuestion(ExamPaper $paper, Question $question)
    {
        DB::transaction(function () use ($paper, $question) {
            $paper->questions()->detach($question->id);
            $paper->update([
                'total_score' => $paper->questions()->sum('score'),
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function attemptResult(PaperAttempt $paperAttempt)
    {
        $paperAttempt->load([
            'user',
            'questions.options',
            'answers.selectedOption',
        ]);

        return view('admin.papers.attempt-result', compact('paperAttempt'));
    }

    public function stats(Request $request, ExamPaper $paper)
    {
        $orgUnitId = $request->query('organization_unit_id');

        $query = PaperAttempt::query()
            ->where('exam_paper_id', $paper->id)
            ->where('status', PaperAttempt::STATUS_SUBMITTED)
            ->with('user.organizationUnit.parent')
            ->orderByDesc('submitted_at');

        if ($orgUnitId === '__none__') {
            $query->whereHas('user', fn ($q) => $q->whereNull('organization_unit_id'));
        } elseif ($orgUnitId) {
            $orgUnit = \App\Models\OrganizationUnit::find($orgUnitId);
            if ($orgUnit && $orgUnit->isRoot()) {
                $leafIds = $orgUnit->children()->pluck('id');
                $query->whereHas('user', fn ($q) => $q->whereIn('organization_unit_id', $leafIds));
            } elseif ($orgUnit) {
                $query->whereHas('user', fn ($q) => $q->where('organization_unit_id', $orgUnitId));
            }
        }

        $attempts = $query->paginate(20)->withQueryString();

        $rootUnits = \App\Models\OrganizationUnit::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $leafUnits = \App\Models\OrganizationUnit::query()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $examPaper = $paper;

        return view('admin.papers.stats', compact('examPaper', 'attempts', 'rootUnits', 'leafUnits', 'orgUnitId'));
    }
}
