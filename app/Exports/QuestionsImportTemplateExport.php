<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class QuestionsImportTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['category_slug', 'stem', 'explanation', 'option_a', 'option_b', 'option_c', 'option_d', 'correct', 'difficulty', 'is_active'],
            ['php-basics', '示例：PHP 中定义类的关键字是？', '解析可留空；题干与选项支持纯文本，导入后可在后台用富文本编辑补图。', 'function', 'class', 'define', 'object', 'B', '2', '1'],
        ];
    }
}
