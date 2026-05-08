@extends('layouts.app')

@section('title', '选择练习分类')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1>选择练习分类</h1>
            <p class="muted">点击“开始练习”将从该分类随机抽取题目。</p>
            @error('category')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>分类</th>
                        <th>可用题目数</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td>{{ $category->questions_count }}</td>
                            <td style="text-align:right;">
                                @if($category->questions_count > 0)
                                    <form method="POST" action="{{ route('student.categories.start', $category) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">开始练习</button>
                                    </form>
                                @else
                                    <span class="pill">暂无题目</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">暂无可用分类。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
