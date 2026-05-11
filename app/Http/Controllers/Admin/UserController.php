<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Imports\UsersImport;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
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

        $users = $query->paginate(20)->withQueryString();

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
            'nameSearch'
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

        $this->userService->createUser($request->validated(), auth()->id());

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

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->route('admin.users.index')->with('status', __('导入完成。'));
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

return response()->json(['message' => '用户已更新。', 'reload' => true]);
    }

    public function destroy(User $user)
    {
        $this->authorizeAdminRoles();
        $this->ensureCanModifyUser(auth()->user(), $user);

        abort_if($user->id === auth()->id(), 403);

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
}
