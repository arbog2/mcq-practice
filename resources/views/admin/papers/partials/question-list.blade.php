@if($questions->count() > 0)
    <div style="margin-top:10px;">
        <label class="row" style="align-items:center;gap:8px;">
            <input type="checkbox" id="select-all-q"> <span class="muted">全选</span>
        </label>
    </div>
    @foreach ($questions as $q)
        <div class="row" style="align-items:flex-start;padding:6px 0;border-bottom:1px solid var(--border);">
            <label class="row" style="align-items:center;gap:8px;flex:1;">
                <input type="checkbox" class="q-cb" value="{{ $q->id }}"
                    {{ in_array($q->id, $selectedIds) ? 'disabled checked' : '' }}>
                <div style="flex:1;min-width:0;">
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ \Illuminate\Support\Str::limit(strip_tags($q->stem), 80) }}</div>
                    <span class="muted" style="font-size:12px;">ID:{{ $q->id }} · {{ $q->category->name ?? '—' }} · {{ $q->score }}分</span>
                </div>
            </label>
            @if(in_array($q->id, $selectedIds))
                <span class="pill" style="flex-shrink:0;">已选</span>
            @endif
        </div>
    @endforeach
    <div class="row" style="margin-top:10px;">
        <button class="btn btn-primary add-q-btn">加入试卷</button>
    </div>
@else
    <p class="muted">未找到匹配的题目。</p>
@endif


