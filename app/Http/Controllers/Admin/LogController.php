<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
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

        $users = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get(['id', 'name', 'username']);

        return view('admin.logs.index', compact('logs', 'type', 'users', 'userId'));
    }
}
