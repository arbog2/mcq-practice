<?php

namespace Tests\Unit\Imports;

use App\Imports\QuestionsImport;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class QuestionsImportTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_import_valid_csv()
    {
        $category = Category::factory()->create(['slug' => 'php-basics']);

        $csv = "stem,category_slug,option_a,option_b,option_c,option_d,correct,explanation,difficulty,is_active,score\n";
        $csv .= 'What is PHP?,php-basics,Option A,Option B,Option C,Option D,A,Explanation text,2,1,1';

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($this->admin);
        $import = new QuestionsImport;
        Excel::import($import, $file);

        $this->assertDatabaseHas('questions', ['stem' => 'What is PHP?']);
    }

    public function test_import_validates_category()
    {
        $csv = "stem,category_slug,option_a,option_b,option_c,option_d,correct,explanation,difficulty,is_active,score\n";
        $csv .= 'Test?,nonexistent-slug,A,B,C,D,A,,1,1,1';

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($this->admin);
        $import = new QuestionsImport;

        $this->expectException(ValidationException::class);
        Excel::import($import, $file);
    }
}
