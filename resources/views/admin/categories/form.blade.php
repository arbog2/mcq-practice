<form method="post" action="{{ $action }}">
    @csrf
    @if($method === 'PUT')
    @method('PUT')
    @endif
    <div class="stack">
        <label>名称<input type="text" name="name" value="{{ $category->name ?? '' }}" required></label>
        <label>Slug<input type="text" name="slug" value="{{ $category->slug ?? '' }}" placeholder="留空自动生成"></label>
        <label>排序<input type="number" name="sort_order" value="{{ $category->sort_order ?? 0 }}" min="0"></label>
        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @if(is_null($category) || $category->is_active) checked @endif> 启用</label>
    </div>
    <div class="stack" style="margin-top:12px;">
        @if(is_null($category))
        <button class="btn btn-primary" type="submit">创建</button>
        @else
        <button class="btn btn-primary" type="submit">更新</button>
        @endif
    </div>
</form>