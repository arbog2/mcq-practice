<?php

namespace Tests\Unit\Models;

use App\Models\Log;
use App\Models\User;
use Tests\TestCase;

class LogTest extends TestCase
{
    public function test_record_creates_log_entry()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        Log::record('测试动作', 'test', '测试描述', ['key' => 'value']);

        $this->assertDatabaseHas('logs', [
            'user_id' => $user->id,
            'action' => '测试动作',
            'type' => 'test',
            'description' => '测试描述',
        ]);
    }

    public function test_record_without_user_stores_null()
    {
        Log::record('test', 'test', 'no user');

        $this->assertDatabaseHas('logs', [
            'user_id' => null,
            'action' => 'test',
        ]);
    }
}
