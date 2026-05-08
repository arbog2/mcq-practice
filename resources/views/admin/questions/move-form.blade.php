<form method="post" action="{{ route('admin.questions.move', $question) }}" id="move-form">
    @csrf
    <div class="stack">
        <div class="muted">题目：{{ \Illuminate\Support\Str::limit(strip_tags($question->stem), 80) }}</div>
        <label>
            <span class="muted">转移到分类</span>
            <select name="category_id" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @if($question->category_id == $cat->id) selected @endif>{{ $cat->name }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
</form>