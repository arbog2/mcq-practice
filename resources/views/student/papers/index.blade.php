@extends('layouts.app')

@section('title', '试卷练习')

@section('content')
    <div class="stack">
        <div class="card stack">
            <h1>试卷练习</h1>
            <p class="muted">选择一套试卷开始练习，系统将自动批改并记录成绩。</p>
            @error('paper')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>试卷名称</th>
                        <th>题目数</th>
                        <th>总分</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($papers as $paper)
                        <tr>
                            <td><strong>{{ $paper->title }}</strong>
                                @if($paper->description)
                                    <br><span class="muted" style="font-size:12px;">{{ $paper->description }}</span>
                                @endif
                            </td>
                            <td>{{ $paper->questions_count }}</td>
                            <td>{{ $paper->total_score }}</td>
                            <td style="text-align:right;">
                                @if($paper->questions_count > 0)
                                    <form method="POST" action="{{ route('student.papers.start', $paper) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">开始作答</button>
                                    </form>
                                @else
                                    <span class="pill">暂无题目</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">暂无可用试卷。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            <a class="btn" href="{{ route('student.papers.history') }}">查看试卷练习历史</a>
        </div>
    </div>
@endsection
