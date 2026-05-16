<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Log;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminManagementController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $nameSearch = trim((string) $request->query('name', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 80, 100]) ? $perPage : (int) config('practice.pagination.users', 20);

        $query = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->where('id', '!=', auth()->id())
            ->orderByDesc('id');

        if ($nameSearch !== '') {
            $query->where('name', 'like', '%'.addcslashes($nameSearch, '%_').'%');
        }

        $users = $query->paginate($perPage)->withQueryString();

        return view('admin.admins.index', compact('users', 'nameSearch', 'perPage'));
    }

    public function create()
    {
        Gate::authorize('create', User::class);
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $leafOrganizationUnits = $this->leafOrganizationUnits();

        return view('admin.admins.form', [
            'user' => null,
            'action' => route('admin.admins.store'),
            'method' => 'POST',
            'assignableRoles' => [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN],
            'leafOrganizationUnits' => $leafOrganizationUnits,
            'allLeafOrganizationUnits' => $leafOrganizationUnits,
            'managedOrgUnitIds' => [],
        ]);
    }

    public function store(StoreAdminRequest $request)
    {
        Gate::authorize('create', User::class);
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $user = $this->userService->createUser($request->validated(), auth()->id());

        Log::record('创建管理员', 'user', '创建管理员：'.$user->username, $request->validated());

        return redirect()->route('admin.admins.index')->with('status', '管理员已创建。');
    }

    public function edit(User $admin)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_if($admin->id === auth()->id(), 403, '不能编辑自己。');

        $leafOrganizationUnits = $this->leafOrganizationUnits();

        return view('admin.admins.form', [
            'user' => $admin,
            'action' => route('admin.admins.update', ['admin' => $admin]),
            'method' => 'PUT',
            'assignableRoles' => [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN],
            'leafOrganizationUnits' => $leafOrganizationUnits,
            'allLeafOrganizationUnits' => $leafOrganizationUnits,
            'managedOrgUnitIds' => $admin->managed_org_unit_ids ?? [],
        ]);
    }

    public function update(UpdateAdminRequest $request, User $admin)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_if($admin->id === auth()->id(), 403, '不能编辑自己。');

        $this->userService->updateUser($admin, $request->validated());

        Log::record('编辑管理员', 'user', '编辑管理员：'.$admin->username, ['user_id' => $admin->id] + $request->validated());

        return redirect()->route('admin.admins.index')->with('status', '管理员已更新。');
    }

    public function destroy(User $admin)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_if($admin->id === auth()->id(), 403, '不能删除自己。');

        Log::record('删除管理员', 'user', '删除管理员：'.$admin->username.' ('.$admin->name.')');

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('status', '管理员已删除。');
    }

    private function leafOrganizationUnits()
    {
        return OrganizationUnit::query()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
