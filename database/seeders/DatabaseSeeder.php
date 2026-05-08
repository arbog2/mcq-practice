<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\OrganizationUnit;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $grade = OrganizationUnit::query()->firstOrCreate(
            ['parent_id' => null, 'name' => '高二'],
            ['sort_order' => 10]
        );

        $classUnit = OrganizationUnit::query()->firstOrCreate(
            ['parent_id' => $grade->id, 'name' => '三班'],
            ['sort_order' => 10]
        );

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'super@example.com'],
            [
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'approval_status' => User::APPROVAL_APPROVED,
                'approved_at' => now(),
                'approved_by' => null,
                'organization_unit_id' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'approval_status' => User::APPROVAL_APPROVED,
                'approved_at' => now(),
                'approved_by' => $superAdmin->id,
                'organization_unit_id' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'username' => 'student',
                'name' => 'Student',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'approval_status' => User::APPROVAL_APPROVED,
                'approved_at' => now(),
                'approved_by' => $superAdmin->id,
                'organization_unit_id' => $classUnit->id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'pending@example.com'],
            [
                'username' => 'pendingstudent',
                'name' => 'Pending Student',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'approval_status' => User::APPROVAL_PENDING,
                'approved_at' => null,
                'approved_by' => null,
                'organization_unit_id' => null,
            ]
        );

        $categoryA = Category::query()->updateOrCreate(
            ['slug' => 'php-basics'],
            [
                'name' => 'PHP 基础',
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $categoryB = Category::query()->updateOrCreate(
            ['slug' => 'mysql-basics'],
            [
                'name' => 'MySQL 基础',
                'sort_order' => 20,
                'is_active' => true,
            ]
        );

        $this->seedQuestion(
            $categoryA,
            '在 PHP 中，哪个关键字用于定义类？',
            'PHP 使用 class 关键字定义类。',
            [
                'function',
                'class',
                'define',
                'object',
            ],
            1
        );

        $this->seedQuestion(
            $categoryB,
            '以下哪个 SQL 语句用于查询数据？',
            'SELECT 用于查询；INSERT/UPDATE/DELETE 用于写入或变更。',
            [
                'INSERT',
                'UPDATE',
                'SELECT',
                'DELETE',
            ],
            2
        );

        $this->seedQuestion(
            $categoryB,
            'MySQL 中主键的最佳描述是？',
            '主键用于唯一标识一行记录。',
            [
                '允许为空',
                '可以重复',
                '唯一且非空（常见实现）',
                '只能是字符串',
            ],
            2
        );
    }

    /**
     * @param  list<string>  $optionTexts
     */
    private function seedQuestion(Category $category, string $stem, string $explanation, array $optionTexts, int $correctIndex): void
    {
        $question = Question::query()->firstOrCreate(
            [
                'category_id' => $category->id,
                'stem' => $stem,
            ],
            [
                'explanation' => $explanation,
                'difficulty' => 2,
                'is_active' => true,
            ]
        );

        $labels = ['A', 'B', 'C', 'D'];

        $question->options()->delete();

        foreach ($optionTexts as $index => $text) {
            QuestionOption::query()->create([
                'question_id' => $question->id,
                'label' => $labels[$index] ?? (string) ($index + 1),
                'content' => $text,
                'is_correct' => $index === $correctIndex,
            ]);
        }
    }
}
