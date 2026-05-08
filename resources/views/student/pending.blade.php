@extends('layouts.app')

@section('title', '审核中')

@section('content')
    <div class="card stack" style="max-width:720px;">
        <h1>账号审核中</h1>
        <p class="muted">你的注册信息已提交，管理员审核通过后即可开始练习。你可随时登录查看状态。</p>

        <div class="row">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn" type="submit">退出登录</button>
            </form>
        </div>
    </div>
@endsection
