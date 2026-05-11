@extends('layouts.app')

@section('title', '错题统计')

@section('content')
    @php
        $exportUrl = route('admin.stats.wrong-by-category.export', array_filter([
            'organization_unit_id' => $organizationUnitId,
            'category_id' => $categoryId,
        ]));
    @endphp

    <div class="stack">
        <div class="card stack">
            <h1 style="margin:0;">错题统计</h1>
            <p class="muted" style="margin:0;">
                先按<strong>用户分类（二级）</strong>，再按<strong>题库分类</strong>，对<strong>每一道题目</strong>统计错误次数。
                口径：学员答卷中判为错误的答题次数（同一学员多次做错同一题会计多次）。
            </p>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">筛选</h2>
            <form method="GET" action="{{ route('admin.stats.wrong-by-category') }}" class="stack">
                <div class="row" style="align-items:flex-end; flex-wrap:wrap;">
                    <div style="min-width:260px;">
                        <label for="organization_unit_id">用户分类（二级）</label>
                        <select id="organization_unit_id" name="organization_unit_id">
                            <option value="">全部</option>
                            @foreach ($leafOrganizationUnits as $unit)
                                <option value="{{ $unit->id }}" @selected((string)$organizationUnitId === (string)$unit->id)>
                                    {{ $unit->fullLabel() }}
                                </option>
                            @endforeach
                            <option value="__none__" @selected((string)$organizationUnitId === '__none__')>
                                未绑定用户分类
                            </option>
                        </select>
                    </div>

                    <div style="min-width:260px;">
                        <label for="category_id">题库分类</label>
                        <select id="category_id" name="category_id">
                            <option value="">全部</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((string)$categoryId === (string)$cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-primary" type="submit">应用筛选</button>
                    <a class="muted" href="{{ route('admin.stats.wrong-by-category') }}">清除</a>
                </div>
            </form>
        </div>

        <div class="card row" style="justify-content:space-between; align-items:flex-end;">
            <div class="muted">共 {{ $rows->total() }} 条聚合记录（用户分类 × 题库分类 × 题目）。</div>
            <a class="btn btn-primary" href="{{ $exportUrl }}">导出 CSV（带筛选）</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>用户分类</th>
                        <th>题库分类</th>
                        <th>题目 ID</th>
                        <th>题干摘要</th>
                        <th style="text-align:right;">错误次数</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $p = isset($row->parent_org_name) ? trim((string) $row->parent_org_name) : '';
                            $c = isset($row->child_org_name) ? trim((string) $row->child_org_name) : '';
                            if ($p !== '' && $c !== '') {
                                $orgLabel = $p.$c;
                            } elseif ($c !== '') {
                                $orgLabel = $c;
                            } else {
                                $orgLabel = '未绑定用户分类';
                            }
                            $stemPreview = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $row->stem))), 140);
                        @endphp
                        <tr>
                            <td>{{ $orgLabel }}</td>
                            <td>{{ $row->category_name }}</td>
                            <td>{{ $row->question_id }}</td>
                            <td style="max-width:520px;">{{ $stemPreview }}</td>
                            <td style="text-align:right;"><strong>{{ $row->wrong_events }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">暂无数据（或筛选条件下没有错题记录）。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $rows->links() }}</div>
    </div>
@endsection
