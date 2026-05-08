<form method="post" action="{{ $action }}" id="question-form">
    @csrf
    @if($method === 'PUT')
    <input type="hidden" name="_method" value="PUT">
    @endif
    <div class="stack">
        <label>分类<select name="category_id" required>
            @foreach($categories as $cat)
            @if(!is_null($question) && $question->category_id == $cat->id)
            <option value="{{ $cat->id }}" selected>{{ $cat->name }}</option>
            @elseif(!is_null($selectedCategoryId) && $selectedCategoryId == $cat->id)
            <option value="{{ $cat->id }}" selected>{{ $cat->name }}</option>
            @else
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endif
            @endforeach
        </select></label>
        
        <label>题目内容 
            <textarea name="stem" id="stem" class="rich-text" required>{{ $question->stem ?? '' }}</textarea>
        </label>
        
        <label>解析 
            <textarea name="explanation" id="explanation" class="rich-text">{{ $question->explanation ?? '' }}</textarea>
        </label>
        
        <label>难度<input type="number" name="difficulty" value="{{ $question->difficulty ?? '' }}" min="1" max="5"></label>
        
        <fieldset><legend>选项（选择正确答案）</legend>
        @for($i = 0; $i < 4; $i++)
        <label class="row">
            <input type="radio" name="correct_index" value="{{ $i }}" @if(!is_null($question) && isset($question->options[$i]) && $question->options[$i]->is_correct) checked @endif>
            <textarea class="rich-text" name="option{{ $i }}" placeholder="选项 {{ chr(65+$i) }}">{{ $question->options[$i]->content ?? '' }}</textarea>
        </label>
        @endfor
        </fieldset>
        
        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @if(is_null($question) || $question->is_active) checked @endif> 启用</label>
    </div>
    <div class="stack" style="margin-top:12px;">
        @if(is_null($question))
        <button class="btn btn-primary" type="submit">创建</button>
        @else
        <button class="btn btn-primary" type="submit">更新</button>
        @endif
    </div>
</form>