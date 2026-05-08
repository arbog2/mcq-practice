@extends('layouts.app')

@section('title', '账号未通过')

@section('content')
    <div class="card stack" style="max-width:720px;">
        <h1>账号审核未通过</h1>
        <p class="muted">如需继续，请联系管理员处理。</p>

        <div class="row">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn" type="submit">退出登录</button>
            </form>
        </div>
    </div>
@endsection
