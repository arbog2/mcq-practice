@extends('layouts.app')

@section('title', '试卷成绩统计 - ' . $examPaper->title)

@section('content')
    <div class="stack">
        <div class="card row" style="justify-content:space-between;align-items:center;">
            <div>
                <h1 style="margin:0;">{{ $examPaper->title }} — 学员成绩</h1>
                <p class="muted" style="margin:4px 0 0;">共 {{ $examPaper->questions_count }} 题，总分 {{ $examPaper->total_score }} 分</p>
            </div>
            <a class="btn" href="{{ route('admin.papers.index') }}">← 返回试卷列表</a>
        </div>

        <div class="card stack">
            <h2 style="margin:0;">筛选</h2>
            <form method="GET" class="row" style="align-items:flex-end; flex-wrap:wrap;">
                <div style="min-width:200px;">
                    <label for="org-level1">一级分类</label>
                    <select id="org-level1">
                        <option value="">全部</option>
                        @foreach ($rootUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width:260px;">
                    <label for="organization_unit_id">二级分类</label>
                    <select id="organization_unit_id" name="organization_unit_id">
                        <option value="">全部</option>
                        @foreach ($leafUnits as $unit)
                            <option
                                value="{{ $unit->id }}"
                                data-parent="{{ $unit->parent_id }}"
                                @selected((string)$orgUnitId === (string)$unit->id)
                            >{{ $unit->fullLabel() }}</option>
                        @endforeach
                        <option value="__none__" @selected((string)$orgUnitId === '__none__')>未绑定用户分类</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">应用筛选</button>
                <a class="muted" href="{{ route('admin.papers.stats', $examPaper) }}">清除</a>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>学员</th>
                        <th>用户分类</th>
                        <th>得分</th>
                        <th>总分</th>
                        <th>正确/总题数</th>
                        <th>用时</th>
                        <th>提交时间</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td><strong>{{ $attempt->user->name ?? '—' }}</strong></td>
                            <td>
                                @if($attempt->user?->organizationUnit)
                                    {{ $attempt->user->organizationUnit->fullLabel() }}
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>{{ $attempt->score }}</td>
                            <td>{{ $attempt->total_score }}</td>
                            <td>{{ $attempt->correct_count }}/{{ $attempt->question_count }}</td>
                            <td>{{ $attempt->duration_seconds ? intdiv($attempt->duration_seconds, 60).'分'.($attempt->duration_seconds % 60).'秒' : '—' }}</td>
                            <td>{{ $attempt->submitted_at ? $attempt->submitted_at->format('Y-m-d H:i') : '—' }}</td>
                            <td>
                                <a class="btn" href="{{ route('admin.papers.attempt.result', $attempt->id) }}" style="font-size:12px;padding:4px 8px;">查看</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted">暂无学员作答记录。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="muted">{{ $attempts->links() }}</div>
    </div>
@endsection

@push('head')
<script>
(function() {
    var level1 = document.getElementById('org-level1');
    var level2 = document.getElementById('organization_unit_id');

    function syncLevel2() {
        var selected = level1.value;
        level2.querySelectorAll('option[data-parent]').forEach(function(opt) {
            opt.style.display = (!selected || opt.getAttribute('data-parent') === selected) ? '' : 'none';
        });
        if (level2.selectedIndex > 0) {
            var cur = level2.options[level2.selectedIndex];
            if (cur.getAttribute('data-parent') && cur.getAttribute('data-parent') !== selected) {
                level2.value = '';
            }
        }
    }

    level1.addEventListener('change', syncLevel2);

    var initVal = level2.value;
    if (initVal) {
        var opt = level2.querySelector('option[value="' + initVal + '"]');
        if (opt && opt.getAttribute('data-parent')) {
            level1.value = opt.getAttribute('data-parent');
        }
    }
    syncLevel2();
})();
</script>
@endpush
