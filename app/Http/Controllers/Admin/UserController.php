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
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $approvalStatus = $request->query('approval_status');
        $orgLevel1Id = $request->query('org_level1_id');
        $orgLevel2Id = $request->query('org_level2_id');
        $nameSearch = trim((string) $request->query('name', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 80, 100]) ? $perPage : (int) config('practice.pagination.users', 20);

        $query = User::query()
            ->with('organizationUnit.parent')
            ->where('role', User::ROLE_STUDENT)
            ->orderByDesc('id');

        /** @var User $actor */
        $actor = auth()->user();

        $this->scopeQueryForActor($actor, $query);

        if ($approvalStatus) {
            $query->where('approval_status', $approvalStatus);
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
            $query->where('name', 'like', '%'.addcslashes($nameSearch, '%_').'%');
        }

        $users = $query->paginate($perPage)->withQueryString();

        $rootOrganizationUnits = $this->rootOrganizationUnits();

        return view('admin.users.index', compact(
            'users',
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
        Gate::authorize('create', User::class);

        $assignableRoles = [User::ROLE_STUDENT];
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
        Gate::authorize('create', User::class);

        $user = $this->userService->createUser($request->validated(), auth()->id());

        Log::record('创建学员', 'user', '创建学员：'.$user->username, $request->validated());

        return response()->json(['message' => '学员已创建。', 'reload' => true]);
    }

    public function importForm()
    {
        Gate::authorize('create', User::class);

        return view('admin.users.import');
    }

    public function importStore(Request $request)
    {
        Gate::authorize('create', User::class);

        set_time_limit(0);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        session_write_close();

        try {
            $import = new UsersImport;
            Excel::import($import, $request->file('file'));

            $progress = Cache::get('import_progress_'.auth()->id(), ['total' => 0]);
            Log::record('导入学员', 'user', '通过 Excel 导入学员');

            return response()->json(['success' => true, 'message' => '导入完成。', 'total' => $progress['total']]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function importProgress()
    {
        Gate::authorize('create', User::class);

        $progress = Cache::get('import_progress_'.auth()->id());

        return response()->json($progress ?? ['total' => 0, 'current' => 0]);
    }

    public function importTemplate()
    {
        Gate::authorize('create', User::class);

        return Excel::download(new UsersImportTemplateExport, 'students-import-template.xlsx');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canManageUser($user), 403);

        $assignableRoles = [User::ROLE_STUDENT];
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
        abort_unless(auth()->user()->canManageUser($user), 403);

        $this->userService->updateUser($user, $request->validated());

        Log::record('编辑学员', 'user', '编辑学员：'.$user->username, ['user_id' => $user->id] + $request->validated());

        return response()->json(['message' => '学员已更新。', 'reload' => true]);
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->canManageUser($user), 403);

        Log::record('删除学员', 'user', '删除学员：'.$user->username.' ('.$user->name.')');

        $user->delete();

        if (request()->ajax()) {
            return response()->json(['message' => '学员已删除。', 'reload' => true]);
        }
        return redirect()->route('admin.users.index')->with('status', __('学员已删除。'));
    }

    public function approve(User $user)
    {
        abort_unless(auth()->user()->canManageUser($user), 403);

        $this->userService->approveUser($user);

        return redirect()->back()->with('status', __('已通过审核。'));
    }

    public function reject(User $user)
    {
        abort_unless(auth()->user()->canManageUser($user), 403);

        $this->userService->rejectUser($user);

        return redirect()->back()->with('status', __('已拒绝该账号。'));
    }

    public function batchDestroy(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
        ]);

        $actor = auth()->user();
        $ids = $validated['ids'];

        $query = User::whereIn('id', $ids)->where('role', User::ROLE_STUDENT);
        $this->scopeQueryForActor($actor, $query);

        $count = $query->count();
        $query->delete();

        Log::record('批量删除学员', 'user', "批量删除 {$count} 个学员");

        return response()->json(['message' => "已批量删除 {$count} 个学员。", 'reload' => true]);
    }

    public function batchMoveCategory(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
            'organization_unit_id' => ['nullable', 'exists:organization_units,id'],
        ]);

        $actor = auth()->user();
        $ids = $validated['ids'];
        $targetOrgId = $validated['organization_unit_id'] ?: null;

        if ($targetOrgId && ! $actor->isSuperAdmin()) {
            $scope = $actor->getManagedOrgUnitIds();
            if (! empty($scope) && ! in_array((int) $targetOrgId, $scope, true)) {
                abort(403, '目标分类超出您的管理范围。');
            }
        }

        $query = User::whereIn('id', $ids)->where('role', User::ROLE_STUDENT);
        $this->scopeQueryForActor($actor, $query);

        $count = $query->update(['organization_unit_id' => $targetOrgId]);

        Log::record('批量转移学员', 'user', "批量转移 {$count} 个学员到分类 {$validated['organization_unit_id']}");

        return response()->json(['message' => "已批量转移 {$count} 个学员。", 'reload' => true]);
    }

    private function leafOrganizationUnits()
    {
        $query = OrganizationUnit::query()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');

        $this->scopeOrgUnitsForActor(auth()->user(), $query);

        return $query->get();
    }

    private function rootOrganizationUnits()
    {
        $roots = OrganizationUnit::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $actor = auth()->user();
        if (! $actor || $actor->isSuperAdmin()) {
            return $roots;
        }

        $scope = $actor->getManagedOrgUnitIds();
        if (empty($scope)) {
            return $roots;
        }

        $childrenToKeep = OrganizationUnit::whereIn('id', $scope)
            ->whereNotNull('parent_id')
            ->get()
            ->keyBy('id');

        $allowedParentIds = $childrenToKeep->pluck('parent_id')->unique();

        foreach ($roots as $root) {
            if (! $allowedParentIds->contains($root->id)) {
                $root->children = collect();
            } else {
                $root->children = $root->children
                    ->filter(fn ($child) => $childrenToKeep->has($child->id))
                    ->values();
            }
        }

        return $roots->filter(fn ($root) => $root->children->isNotEmpty())->values();
    }

    private function scopeOrgUnitsForActor(User $actor, $query): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $scope = $actor->getManagedOrgUnitIds();
        if (! empty($scope)) {
            $query->whereIn('id', $scope);
        }
    }

    private function scopeQueryForActor(User $actor, \Illuminate\Database\Eloquent\Builder $query): void
    {
        if ($actor->isSuperAdmin()) {
            $query->where('id', '!=', $actor->id);
            return;
        }

        $scope = $actor->getManagedOrgUnitIds();
        if (! empty($scope)) {
            $query->whereIn('organization_unit_id', $scope);
        }
    }
}
