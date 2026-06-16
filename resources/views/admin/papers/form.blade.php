@extends('layouts.app')

@section('title', $paper ? '编辑试卷' : '新建试卷')

@section('content')
    <div class="card stack" style="max-width:600px;">
        <h1>{{ $paper ? '编辑试卷' : '新建试卷' }}</h1>

        <form method="POST" action="{{ $action }}">
            @csrf
            @method($method)

            <div class="stack">
                <div>
                    <label for="title">试卷名称</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $paper->title ?? '') }}" required>
                    @error('title') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="description">试卷描述（可选）</label>
                    <textarea name="description" id="description" rows="3">{{ old('description', $paper->description ?? '') }}</textarea>
                    @error('description') <div class="error">{{ $message }}</div> @enderror
                </div>

                @if($paper)
                <div>
                    <label>
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $paper->is_active) ? 'checked' : '' }}>
                        启用
                    </label>
                </div>
                @endif
            </div>

            <div class="row" style="margin-top:16px;">
                <button class="btn btn-primary" type="submit">{{ $paper ? '保存' : '创建并组卷' }}</button>
                <a class="btn" href="{{ route('admin.papers.index') }}">取消</a>
            </div>
        </form>
    </div>
@endsection
