@extends('layouts.app')

@section('title', '新建题目')

@section('content')
    <div class="card stack" style="max-width:920px;">
        <h1>新建题目</h1>
        <p class="muted">固定 4 个选项；选择一个为正确答案。题干/选项/解析支持富文本与插图（工具栏插入图片）。</p>

        <form method="POST" action="{{ route('admin.questions.store') }}" class="stack">
            @csrf

            <div>
                <label for="category_id">分类</label>
                <select id="category_id" name="category_id" required>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((string)old('category_id', $selectedCategoryId) === (string)$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="stem">题干</label>
                <textarea id="stem" class="tinymce" name="stem" required>{{ old('stem') }}</textarea>
                @error('stem') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="explanation">解析（可选）</label>
                <textarea id="explanation" class="tinymce" name="explanation">{{ old('explanation') }}</textarea>
                @error('explanation') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label for="difficulty">难度（1-5，可选）</label>
                <input id="difficulty" type="number" name="difficulty" value="{{ old('difficulty') }}" min="1" max="5">
                @error('difficulty') <div class="error">{{ $message }}</div> @enderror
            </div>

            <label class="row" style="user-select:none;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                <span class="muted">启用</span>
            </label>

            <div class="card stack">
                <h2 style="margin:0;">选项（4 个）</h2>
                @for ($i = 0; $i < 4; $i++)
                    <div>
                        <label for="option_{{ $i }}">选项 {{ $i + 1 }}</label>
                        <textarea id="option_{{ $i }}" class="tinymce" name="options[{{ $i }}][content]" required>{{ old('options.'.$i.'.content') }}</textarea>
                        @error('options.'.$i.'.content') <div class="error">{{ $message }}</div> @enderror
                    </div>
                @endfor
                @error('options') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="card stack">
                <div class="muted">正确答案</div>
                @for ($i = 0; $i < 4; $i++)
                    <label class="row" style="user-select:none;">
                        <input type="radio" name="correct_index" value="{{ $i }}" @checked((string)old('correct_index', '0') === (string)$i)>
                        <span>选项 {{ $i + 1 }}</span>
                    </label>
                @endfor
                @error('correct_index') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">保存</button>
                <a class="muted" href="{{ route('admin.questions.index') }}">返回</a>
            </div>
        </form>
    </div>

    @include('admin.partials.tinymce-setup')
@endsection
