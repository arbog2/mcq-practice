<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $type = $request->query('type');
        $userId = $request->query('user_id');

        $query = Log::query()->with('user')->orderByDesc('id');

        if ($type) {
            $query->where('type', $type);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $logs = $query->paginate(30)->withQueryString();

        $users = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->orderBy('name')->get(['id', 'name', 'username']);

        return view('admin.logs.index', compact('logs', 'type', 'users', 'userId'));
    }
}
