<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $errors = [];
        
        DB::transaction(function () use ($rows, &$errors) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $r = $row instanceof Collection ? $row->toArray() : (array) $row;
     
                $stem = trim((string) ($r['stem'] ?? ''));
                if ($stem === '') {
                    continue;
                }

                $categorySlug = trim((string) ($r['category_slug'] ?? ''));
                if ($categorySlug === '') {
                    $errors[] = __('第 :row 行：category_slug 不能为空。', ['row' => $rowNumber]);
                    continue;
                }

                $category = Category::query()->where('slug', $categorySlug)->first();
                if (! $category) {
                    $errors[] = __('第 :row 行：找不到分类 slug「:slug」，请先在分类管理中创建。', ['row' => $rowNumber, 'slug' => $categorySlug]);
                    continue;
                }

                $optionA = trim((string) ($r['option_a'] ?? ''));
                $optionB = trim((string) ($r['option_b'] ?? ''));
                $optionC = trim((string) ($r['option_c'] ?? ''));
                $optionD = trim((string) ($r['option_d'] ?? ''));

                foreach (['A' => $optionA, 'B' => $optionB, 'C' => $optionC, 'D' => $optionD] as $label => $content) {
                    if ($content === '') {
                        $errors[] = __('第 :row 行：选项 :label 不能为空。', ['row' => $rowNumber, 'label' => $label]);
                        continue 2;
                    }
                }

                $correctRaw = trim((string) ($r['correct'] ?? ''));
                if ($correctRaw === '') {
                    $errors[] = __('第 :row 行：correct（正确答案）不能为空，请填写 A/B/C/D 或 0-3。', ['row' => $rowNumber]);
                    continue;
                }

                $correctIndex = $this->parseCorrectIndex($correctRaw);
                if ($correctIndex === null) {
                    $errors[] = __('第 :row 行：correct 无效（应为 A/B/C/D 或 0-3）。', ['row' => $rowNumber]);
                    continue;
                }

                $explanation = trim((string) ($r['explanation'] ?? ''));
                $explanation = $explanation !== '' ? $explanation : null;

                $difficultyRaw = $r['difficulty'] ?? null;
                $difficulty = null;
                if ($difficultyRaw !== null && $difficultyRaw !== '') {
                    $difficulty = (int) $difficultyRaw;
                    if ($difficulty < 1 || $difficulty > 5) {
                        $errors[] = __('第 :row 行：difficulty 需在 1-5 之间或留空。', ['row' => $rowNumber]);
                        continue;
                    }
                }

                $isActive = $this->parseIsActive($r['is_active'] ?? null);

                $options = [$optionA, $optionB, $optionC, $optionD];
                $labels = ['A', 'B', 'C', 'D'];

                $question = Question::query()->create([
                    'category_id' => $category->id,
                    'stem' => $stem,
                    'explanation' => $explanation,
                    'difficulty' => $difficulty,
                    'is_active' => $isActive,
                ]);

                $questionOptions = [];
                foreach ($options as $idx => $content) {
                    $questionOptions[] = [
                        'question_id' => $question->id,
                        'label' => $labels[$idx],
                        'content' => $content,
                        'is_correct' => $idx === $correctIndex,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                QuestionOption::query()->insert($questionOptions);
            }
        });

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }
    }

    private function parseCorrectIndex(string $raw): ?int
    {
        $u = strtoupper($raw);

        if (in_array($u, ['A', 'B', 'C', 'D'], true)) {
            return ord($u) - ord('A');
        }

        if (preg_match('/^[0-3]$/', $raw) === 1) {
            return (int) $raw;
        }

        return null;
    }

    private function parseIsActive(mixed $raw): bool
    {
        if ($raw === null || $raw === '') {
            return true;
        }

        if (is_bool($raw)) {
            return $raw;
        }

        $s = strtolower(trim((string) $raw));

        return match ($s) {
            '0', 'false', 'no', 'n', '否', 'off' => false,
            default => true,
        };
    }
}
