<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="upload-url" content="{{ route('admin.editor.upload-image') }}">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            --bg: #f4f6fb;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --primary: #2563eb;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Microsoft YaHei", sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
        }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 16px 20px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .brand { font-weight: 700; letter-spacing: 0.2px; }
        .nav { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; color: var(--muted); font-size: 14px; }
        main .wrap { padding: 22px 20px 40px; }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }
        h1 { font-size: 22px; margin: 0 0 12px; }
        h2 { font-size: 18px; margin: 0 0 10px; }
        .muted { color: var(--muted); font-size: 14px; }
        .btn {
            display: inline-block;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-danger { background: var(--danger); border-color: var(--danger); color: #fff; }
        .stack { display: grid; gap: 12px; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea {
            width: 100%;
            max-width: 680px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            font-size: 14px;
        }
        textarea { min-height: 110px; resize: vertical; }
        .error { color: var(--danger); font-size: 13px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border-bottom: 1px solid var(--border); padding: 10px 8px; text-align: left; vertical-align: top; }
        th { color: var(--muted); font-weight: 600; font-size: 13px; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; border: 1px solid var(--border); font-size: 12px; color: var(--muted); }
        .status { margin: 10px 0; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #fff; font-size: 14px; }
        .pagination { display:flex; gap:6px; list-style:none; padding:0; margin:10px 0; flex-wrap:nowrap; align-items:center; }
        .pagination .page-item { display:inline-block; margin:0; flex-shrink:0; }
        .pagination .page-link { display:inline-block; padding:4px 10px; border:1px solid var(--border); border-radius:8px; background:#fff; color:var(--text); font-size:14px; text-decoration:none; }
        .pagination .page-item.active .page-link { border-color:var(--primary); color:var(--primary); font-weight:600; }
        .pagination .page-item.disabled .page-link { color:var(--muted); opacity:0.5; cursor:default; }
        .question-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:6px; min-width:170px; }
        .single-mode-form { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-start; padding-left:16px; }
        .single-mode-form #questions-container { flex:1; min-width:0; }
        .single-mode-form #single-nav { position:sticky; top:20px; flex-shrink:0; display:flex; flex-direction:column; background:#fff; padding:12px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.12); border:1px solid var(--border); }
        .single-mode-form #single-bottom { width:100%; }
        @media (max-width:767px) {
            .mobile-mode-form #questions-container .question-card { margin:0 -4px; }
            .mobile-mode-form #mobile-nav-top .card { border-radius:0; border-left:none; border-right:none; }
            #mode-select { display:none; }
        }
        .qnum-btn { width:36px; height:36px; border-radius:8px; border:1px solid var(--border); background:#fff; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; padding:0; }
        .qnum-btn.active { border-color:var(--primary); color:var(--primary); font-weight:700; }
        .qnum-btn.answered { background:#fecaca; border-color:#fca5a5; }
        .qnum-btn.unanswered { background:#bbf7d0; border-color:#86efac; }
        .rich-text { line-height: 1.75; font-size: 15px; }
        .rich-text img { max-width: 100%; height: auto; display: block; margin: 8px 0; }
        .rich-text p { margin: 0.35em 0; }
        .modal {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;
            display: none; align-items: center; justify-content: center;
        }
        .modal[data-open="1"] { display: flex; }
        .modal-backdrop {
            position: absolute; inset: 0; background: rgba(0,0,0,0.4);
        }
        .modal-content {
            position: relative; background: #fff; border-radius: 12px; width: 90%; max-width: 1040px;
            max-height: 90vh; overflow: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center; padding: 14px 18px;
            border-bottom: 1px solid var(--border);
        }
        .modal-header h3 { margin: 0; font-size: 16px; }
        .modal-close {
            background: none; border: none; font-size: 22px; cursor: pointer; color: var(--muted);
        }
        .modal-body { padding: 18px; }

        /* 后台左栏布局 */
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-layout header {
            width: 220px; flex-shrink: 0; border-bottom: none;
            border-right: 1px solid var(--border); display: flex;
            flex-direction: column; position: sticky; top: 0; height: 100vh;
        }
        .admin-layout header .topbar {
            flex-direction: column; align-items: stretch; max-width: none;
            padding: 20px 16px; flex: 1;
        }
        .admin-layout header .nav { flex-direction: column; gap: 4px; }
        .admin-layout header .nav a {
            display: block; padding: 8px 12px; border-radius: 8px; font-size: 14px;
        }
        .admin-layout header .nav a:hover { background: #f3f4f6; }
        .admin-layout main { flex: 1; min-width: 0; overflow-x: auto; }
        .admin-layout main .wrap { max-width: none; padding: 22px 24px 40px; }
        .admin-layout .logout-wrap { margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border); }
    </style>
    @stack('head')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</head>
<body class="{{ auth()->user()?->isAdmin() ? 'admin-layout' : '' }}">
    <header>
        <div class="wrap topbar">
            <div class="brand"><a href="{{ route('home') }}">{{ config('app.name') }}</a></div>
            @auth
                @if(auth()->user()->isAdmin())
            <nav class="nav">
                        <a href="{{ route('admin.dashboard') }}">后台</a>
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.categories.index') }}">题库分类</a>
                        @endif
                        <a href="{{ route('admin.questions.index') }}">题库</a>
                        @if(auth()->user()->canManageUsers())
                            <a href="{{ route('admin.users.index') }}">学员</a>
                        @endif
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.organization-units.index') }}">学员分类</a>
                        @endif
                        <a href="{{ route('admin.stats.wrong-by-category') }}">错题统计</a>
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.admins.index') }}">管理员</a>
                            <a href="{{ route('admin.logs.index') }}">操作日志</a>
                            <a href="{{ route('admin.settings.index') }}">系统设置</a>
                        @endif
                        <a href="{{ route('admin.profile.edit') }}">我的资料</a>
            </nav>
            <div class="logout-wrap">
                <span class="muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button class="btn" type="submit">退出</button>
                </form>
            </div>
                @else
            <nav class="nav">
                        <a href="{{ route('student.dashboard') }}">学员首页</a>
                        <a href="{{ route('student.categories') }}">开始练习</a>
                        <a href="{{ route('student.attempts.history') }}">练习历史</a>
                        <a href="{{ route('student.wrong-book') }}">错题本</a>
                        <a href="{{ route('student.profile.edit') }}">个人资料</a>
                <span class="muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button class="btn" type="submit">退出</button>
                </form>
            </nav>
                @endif
            @else
            <nav class="nav">
                <a href="{{ route('login') }}">登录</a>
                @if($settings['registration_enabled'])
                    <a href="{{ route('register') }}">注册</a>
                @endif
            </nav>
            @endauth
        </div>
    </header>

    <main>
        <div class="wrap">
            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </div>
    </main>


</body>
</html>
