<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        $perPage = (int) $request->query('per_page', config('practice.pagination.questions', 20));
        $perPage = in_array($perPage, [20, 40, 80, 100]) ? $perPage : 20;
        $query = Question::query()->with('category')->orderByDesc('id');
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        $questions = $query->paginate($perPage)->withQueryString();
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.questions.index', compact('questions', 'categories', 'categoryId', 'perPage'));
    }

    public function create(Request $request)
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.questions.form', [
            'question' => null,
            'action' => route('admin.questions.store'),
            'method' => 'POST',
            'categories' => $categories,
            'selectedCategoryId' => $request->query('category_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'stem' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
            'option0' => ['required', 'string'],
            'option1' => ['required', 'string'],
            'option2' => ['required', 'string'],
            'option3' => ['required', 'string'],
            'correct_index' => ['required', 'integer', 'min:0', 'max:3'],
        ]);

        $labels = ['A', 'B', 'C', 'D'];
        $options = [];
        for ($i = 0; $i < 4; $i++) {
            $options[] = [
                'label' => $labels[$i],
                'content' => $validated['option'.$i],
                'is_correct' => ($i === (int) $validated['correct_index']),
            ];
        }

        DB::transaction(function () use ($validated, $options, $request) {
            $question = Question::create([
                'category_id' => $validated['category_id'],
                'stem' => $validated['stem'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty' => $validated['difficulty'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);
            foreach ($options as $opt) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'label' => $opt['label'],
                    'content' => $opt['content'],
                    'is_correct' => $opt['is_correct'],
                ]);
            }
        });

        return response()->json(['message' => '题目已创建。', 'reload' => true]);
    }

    public function edit(Question $question)
    {
        $question->load('options');
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.questions.form', [
            'question' => $question,
            'action' => route('admin.questions.update', $question),
            'method' => 'PUT',
            'categories' => $categories,
            'selectedCategoryId' => null,
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'stem' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
            'option0' => ['required', 'string'],
            'option1' => ['required', 'string'],
            'option2' => ['required', 'string'],
            'option3' => ['required', 'string'],
            'correct_index' => ['required', 'integer', 'min:0', 'max:3'],
        ]);

        $labels = ['A', 'B', 'C', 'D'];
        $options = [];
        for ($i = 0; $i < 4; $i++) {
            $options[] = [
                'label' => $labels[$i],
                'content' => $validated['option'.$i],
                'is_correct' => ($i === (int) $validated['correct_index']),
            ];
        }

        DB::transaction(function () use ($validated, $options, $question, $request) {
            $question->update([
                'category_id' => $validated['category_id'],
                'stem' => $validated['stem'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty' => $validated['difficulty'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);
            $question->options()->delete();
            foreach ($options as $opt) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'label' => $opt['label'],
                    'content' => $opt['content'],
                    'is_correct' => $opt['is_correct'],
                ]);
            }
        });

        return response()->json(['message' => '题目已更新。', 'reload' => true]);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return response()->json(['message' => '题目已删除。', 'reload' => true]);
    }

    public function moveForm(Question $question)
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.questions.move-form', compact('question', 'categories'));
    }

    public function moveCategory(Request $request, Question $question)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
        ]);
        $question->update(['category_id' => $validated['category_id']]);
        return response()->json(['message' => '分类已更新。', 'reload' => true]);
    }

    public function batchMoveCategory(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:questions,id'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);
        $count = Question::whereIn('id', $validated['ids'])->update(['category_id' => $validated['category_id']]);
        return response()->json(['message' => "已批量转移 {$count} 道题目。", 'reload' => true]);
    }

    public function batchDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:questions,id'],
        ]);
        $count = Question::whereIn('id', $validated['ids'])->delete();
        return response()->json(['message' => "已批量删除 {$count} 道题目。", 'reload' => true]);
    }

    public function importForm()
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.questions.import', compact('categories'));
    }

    public function importStore(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
        try {
            Excel::import(new \App\Imports\QuestionsImport, $request->file('file'));
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
        return redirect()->route('admin.questions.index')->with('status', '题目导入完成。');
    }

    public function importTemplate()
    {
        return Excel::download(new \App\Exports\QuestionsImportTemplateExport, 'questions-import-template.xlsx');
    }
}