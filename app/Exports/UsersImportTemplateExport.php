<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class UsersImportTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['username', 'email', 'name', 'password', 'level_1', 'level_2'],
            ['zhangsan', 'zhangsan@example.com', '张三', 'password123', '高二', '三班'],
        ];
    }
}
