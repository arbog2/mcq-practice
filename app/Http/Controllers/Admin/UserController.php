<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Imports\UsersImport;
use App\Models\Log;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request)
    {
        $role = $request->query('role');
        $approvalStatus = $request->query('approval_status');
        $orgLevel1Id = $request->query('org_level1_id');
        $orgLevel2Id = $request->query('org_level2_id');
        $nameSearch = trim((string) $request->query('name', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 40, 50, 100]) ? $perPage : 10;

        $query = User::query()
            ->with('organizationUnit.parent')
            ->orderByDesc('id');

        /** @var User $actor */
        $actor = auth()->user();
        if ($actor && $actor->role === User::ROLE_ADMIN) {
            $query->where('role', '!=', User::ROLE_SUPER_ADMIN);
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($approvalStatus) {
            $query->where('approval_status', $approvalStatus)
                ->where('role', User::ROLE_STUDENT);
        }

        if ($orgLevel2Id !== null && $orgLevel2Id !== '') {
            $query->where('organization_unit_id', $orgLevel2Id);
        } elseif ($orgLevel1Id !== null && $orgLevel1Id !== '') {
            $leafIds = OrganizationUnit::query()
                ->where('parent_id', $orgLevel1Id)
                ->pluck('id');

            $query->whereIn('organization_unit_id', $leafIds);
        }

        if ($nameSearch !== '') {
            $query->where('name', 'like', '%'.$nameSearch.'%');
        }

        $users = $query->paginate($perPage)->withQueryString();

        $rootOrganizationUnits = OrganizationUnit::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact(
            'users',
            'role',
            'approvalStatus',
            'rootOrganizationUnits',
            'orgLevel1Id',
            'orgLevel2Id',
            'nameSearch',
            'perPage'
        ));
    }

    public function create()
    {
        $this->authorizeAdminRoles();

        $assignableRoles = $this->assignableRolesFor(auth()->user());
        $leafOrganizationUnits = $this->leafOrganizationUnits();

        return view('admin.users.form', [
            'user' => null,
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'assignableRoles' => $assignableRoles,
            'leafOrganizationUnits' => $leafOrganizationUnits,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorizeAdminRoles();
        $this->ensureRoleAssignable(auth()->user(), $request->validated()['role']);

        $user = $this->userService->createUser($request->validated(), auth()->id());

        Log::record('创建用户', 'user', '创建用户：'.$user->username, $request->validated());

        return response()->json(['message' => '用户已创建。', 'reload' => true]);
    }

    public function importForm()
    {
        $this->authorizeAdminRoles();

        return view('admin.users.import');
    }

    public function importStore(Request $request)
    {
        $this->authorizeAdminRoles();

        set_time_limit(0);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $import = new UsersImport;
            Excel::import($import, $request->file('file'));

            Log::record('导入用户', 'user', '通过 Excel 导入用户');

            return response()->json(['success' => true, 'message' => '导入完成。']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function importProgress()
    {
        $this->authorizeAdminRoles();

        $progress = Cache::get('import_progress_'.auth()->id());

        return response()->json($progress ?? ['total' => 0, 'current' => 0]);
    }

    public function importTemplate()
    {
        $this->authorizeAdminRoles();

        return Excel::download(new UsersImportTemplateExport, 'users-import-template.xlsx');
    }

    public function edit(User $user)
    {
        $this->authorizeAdminRoles();
        $this->ensureCanModifyUser(auth()->user(), $user);

        $assignableRoles = $this->assignableRolesFor(auth()->user());
        $leafOrganizationUnits = $this->leafOrganizationUnits();

        return view('admin.users.form', [
            'user' => $user,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'assignableRoles' => $assignableRoles,
            'leafOrganizationUnits' => $leafOrganizationUnits,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorizeAdminRoles();
        $this->ensureCanModifyUser(auth()->user(), $user);
        $this->ensureRoleAssignable(auth()->user(), $request->validated()['role']);

        $this->userService->updateUser($user, $request->validated());

        Log::record('编辑用户', 'user', '编辑用户：'.$user->username, ['user_id' => $user->id] + $request->validated());

return response()->json(['message' => '用户已更新。', 'reload' => true]);
    }

    public function destroy(User $user)
    {
        $this->authorizeAdminRoles();
        $this->ensureCanModifyUser(auth()->user(), $user);

        abort_if($user->id === auth()->id(), 403);

        Log::record('删除用户', 'user', '删除用户：'.$user->username.' ('.$user->name.')');

        $user->delete();

        if (request()->ajax()) {
            return response()->json(['message' => '用户已删除。', 'reload' => true]);
        }
        return redirect()->route('admin.users.index')->with('status', __('用户已删除。'));
    }

    public function approve(User $user)
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        $this->userService->approveUser($user);

        return redirect()->back()->with('status', __('已通过审核。'));
    }

    public function reject(User $user)
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        $this->userService->rejectUser($user);

        return redirect()->back()->with('status', __('已拒绝该账号。'));
    }

    public function batchDestroy(Request $request)
    {
        $this->authorizeAdminRoles();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
        ]);

        $actor = auth()->user();
        $ids = $validated['ids'];

        $query = User::whereIn('id', $ids);
        $this->filterUsersForActor($actor, $query);

        $count = $query->count();
        $query->delete();

        Log::record('批量删除用户', 'user', "批量删除 {$count} 个用户");

        return response()->json(['message' => "已批量删除 {$count} 个用户。", 'reload' => true]);
    }

    public function batchMoveCategory(Request $request)
    {
        $this->authorizeAdminRoles();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
            'organization_unit_id' => ['nullable', 'exists:organization_units,id'],
        ]);

        $actor = auth()->user();
        $ids = $validated['ids'];

        $query = User::whereIn('id', $ids);
        $this->filterUsersForActor($actor, $query);

        $count = $query->update(['organization_unit_id' => $validated['organization_unit_id'] ?: null]);

        Log::record('批量转移用户', 'user', "批量转移 {$count} 个用户到分类 {$validated['organization_unit_id']}");

        return response()->json(['message' => "已批量转移 {$count} 个用户。", 'reload' => true]);
    }

    private function authorizeAdminRoles(): void
    {
        /** @var User $actor */
        $actor = auth()->user();

        abort_unless($actor && $actor->isAdmin(), 403);
    }

    private function ensureRoleAssignable(User $actor, string $role): void
    {
        if ($role === User::ROLE_SUPER_ADMIN) {
            abort_unless($actor->isSuperAdmin(), 403);
        }

        if ($role === User::ROLE_ADMIN) {
            abort_unless($actor->isSuperAdmin(), 403);
        }
    }

    private function ensureCanModifyUser(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            abort_unless($actor->isSuperAdmin(), 403, '不能编辑或删除自己。');

            return;
        }

        if ($target->isSuperAdmin()) {
            abort_unless($actor->isSuperAdmin(), 403);

            return;
        }

        if ($target->isAdmin()) {
            abort_unless($actor->isSuperAdmin(), 403);

            return;
        }

        if ($actor->isAdmin() && $target->role !== User::ROLE_STUDENT) {
            abort(403);
        }
    }

    /**
     * @return list<string>
     */
    private function assignableRolesFor(User $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return [User::ROLE_STUDENT, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN];
        }

        if ($actor->role === User::ROLE_ADMIN) {
            return [User::ROLE_STUDENT];
        }

        return [];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, OrganizationUnit>
     */
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

    private function filterUsersForActor(User $actor, \Illuminate\Database\Eloquent\Builder $query): void
    {
        if ($actor->isSuperAdmin()) {
            $query->where('id', '!=', $actor->id);
        } else {
            $query->where('role', User::ROLE_STUDENT);
        }
    }
}
