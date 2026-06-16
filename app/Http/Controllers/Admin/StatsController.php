<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatsController extends Controller
{
    private function applyDefaultSorting(Builder $query): Builder
    {
        return $query
            ->orderByRaw('COALESCE(MAX(parent.name), "") asc')
            ->orderByRaw('COALESCE(MAX(ou.name), "") asc')
            ->orderByRaw('MAX(c.name) asc')
            ->orderByDesc('wrong_events')
            ->orderBy('question_id');
    }

    public function wrongByCategory(Request $request)
    {
        $organizationUnitId = $request->query('organization_unit_id');
        $categoryId = $request->query('category_id');

        $perPage = (int) config('practice.pagination.questions', 20);
        $rows = $this->applyDefaultSorting($this->wrongStatsQuery($request))
            ->paginate($perPage)
            ->withQueryString();

        $leafOrganizationUnits = OrganizationUnit::query()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.stats.wrong-by-category', compact(
            'rows',
            'leafOrganizationUnits',
            'categories',
            'organizationUnitId',
            'categoryId'
        ));
    }

    public function exportWrongByCategory(Request $request): StreamedResponse
    {
        $rows = $this->applyDefaultSorting($this->wrongStatsQuery($request))->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="wrong-stats-by-org-and-category.csv"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'organization_unit_id',
                'user_org_level1',
                'user_org_level2',
                'user_org_label',
                'question_category_id',
                'question_category_name',
                'question_id',
                'wrong_events',
                'source',
                'stem_plain_preview',
            ]);

            foreach ($rows as $row) {
                $stemPreview = strip_tags((string) $row->stem);
                $stemPreview = preg_replace('/\s+/u', ' ', $stemPreview);

                fputcsv($out, [
                    $row->organization_unit_id ?? '',
                    $row->parent_org_name ?? '',
                    $row->child_org_name ?? '',
                    $this->formatOrgLabel($row),
                    $row->category_id,
                    $row->category_name,
                    $row->question_id,
                    $row->wrong_events,
                    $row->sources ?? '—',
                    mb_substr((string) $stemPreview, 0, 200),
                ]);
            }

            fclose($out);
        }, 'wrong-stats-by-org-and-category.csv', $headers);
    }

    /**
     * 统计口径：学员答卷中判错的记录条数（同一学员多次做错同一题会计多次）。
     *
     * 聚合维度：用户二级分类（organization_unit_id）→ 题库分类 → 题目。
     * 数据来源：分类练习（practice_attempt_answers）+ 试卷练习（paper_attempt_answers）。
     */
    private function wrongStatsQuery(Request $request): Builder
    {
        $organizationUnitId = $request->query('organization_unit_id');
        $categoryId = $request->query('category_id');

        $sub = DB::table('practice_attempt_answers as paa')
            ->join('practice_attempts as pa', 'pa.id', '=', 'paa.practice_attempt_id')
            ->where('paa.is_correct', false)
            ->where('pa.status', PaperAttempt::STATUS_SUBMITTED)
            ->select([
                'pa.user_id',
                'paa.question_id',
                DB::raw("'分类练习' as source"),
            ]);

        $sub2 = DB::table('paper_attempt_answers as paa2')
            ->join('paper_attempts as pa2', 'pa2.id', '=', 'paa2.paper_attempt_id')
            ->where('paa2.is_correct', false)
            ->where('pa2.status', PaperAttempt::STATUS_SUBMITTED)
            ->select([
                'pa2.user_id',
                'paa2.question_id',
                DB::raw("'试卷练习' as source"),
            ]);

        $union = $sub->unionAll($sub2);

        $query = DB::table(DB::raw("({$union->toSql()}) as wrongs"))
            ->mergeBindings($union)
            ->join('users as u', 'u.id', '=', 'wrongs.user_id')
            ->leftJoin('organization_units as ou', 'ou.id', '=', 'u.organization_unit_id')
            ->leftJoin('organization_units as parent', 'parent.id', '=', 'ou.parent_id')
            ->join('questions as q', 'q.id', '=', 'wrongs.question_id')
            ->join('categories as c', 'c.id', '=', 'q.category_id')
            ->where('u.role', User::ROLE_STUDENT);

        if ($organizationUnitId === '__none__') {
            $query->whereNull('u.organization_unit_id');
        } elseif ($organizationUnitId !== null && $organizationUnitId !== '') {
            $query->where('u.organization_unit_id', $organizationUnitId);
        }

        if ($categoryId !== null && $categoryId !== '') {
            $query->where('c.id', $categoryId);
        }

        return $query->select([
            DB::raw('u.organization_unit_id as organization_unit_id'),
            DB::raw('MAX(parent.name) as parent_org_name'),
            DB::raw('MAX(ou.name) as child_org_name'),
            'c.id as category_id',
            DB::raw('MAX(c.name) as category_name'),
            'q.id as question_id',
            DB::raw('MAX(q.stem) as stem'),
            DB::raw('COUNT(*) as wrong_events'),
            DB::raw("GROUP_CONCAT(DISTINCT wrongs.source ORDER BY wrongs.source SEPARATOR '、') as sources"),
        ])->groupBy('u.organization_unit_id', 'c.id', 'q.id');
    }

    private function formatOrgLabel(object $row): string
    {
        $p = isset($row->parent_org_name) ? trim((string) $row->parent_org_name) : '';
        $c = isset($row->child_org_name) ? trim((string) $row->child_org_name) : '';

        if ($p !== '' && $c !== '') {
            return $p.' — '.$c;
        }

        if ($c !== '') {
            return $c;
        }

        return __('未绑定用户分类');
    }
}
