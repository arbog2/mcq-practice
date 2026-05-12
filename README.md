# 选择题练习系统 (MCQ Practice System)

基于 Laravel 11 的多用户选择题在线练习系统，支持管理员题库管理、学员练习、错题本等功能。

## 功能

### 学员端
- **分类练习** — 按分类选择练习，随机抽题，选项内容打乱后按 A/B/C/D 顺序显示
- **双模式作答** — 支持"一页全显示"和"一页一题"两种模式，可随时切换
  - 一页一题模式：左侧宫格显示题号（绿色=未答，红色=已答），上一题/下一题/提交在同一行
- **自动评分** — 提交后即时评分，展示全部四个选项、正确答案和解析
- **错题本** — 自动记录错题，可按分类筛选
- **错题重练** — 从错题中抽取题目重新练习，答对自动标记已掌握
  - 整体抽取：从所有错题中随机抽题
  - 分类抽取：从指定分类的错题中抽题
- **练习历史** — 查看历史练习记录和成绩
- **个人信息编辑**

### 管理端
- **题库管理** — 题目的增删改查
  - 题干关键词模糊检索，可结合分类筛选
  - 快速分类转移：操作列一键转移单题分类
  - 批量分类转移：多选题目后批量转移到新分类
  - 批量删除：多选题目后批量删除
  - 每页条数选择（20/40/80/100）
  - 支持从 Excel 导入题目
- **分类管理** — 分类的增删改查，支持排序
- **用户管理** — 用户的增删改查，支持 Excel 导入、审核注册
  - 角色权限层级：`super_admin` 可管理所有角色；`admin` 仅可管理 `student` 角色用户，不可编辑/删除自身
  - 用户列表：`admin` 角色登录时不显示 `super_admin` 用户
- **用户分类管理** — 组织单元管理
- **系统设置**
  - 开启/关闭自助注册
  - 注册是否需要审核
  - 每次练习的题量
- **错题统计** — 按分类统计错题分布，支持导出
- **操作日志** — 记录管理员登录、用户增删改、题目增删改/导入/转移等操作（仅 `super_admin` 可查看）

### 通用
- **用户认证** — 登录、注册、密码重置、邮箱验证
- **角色权限** — 三级角色：`super_admin`、`admin`、`student`
- **富文本编辑器** — 题干和选项支持图文编辑（TinyMCE），粘贴图片自动上传至服务器

## 技术栈

| 组件 | 技术 |
|------|------|
| 后端框架 | Laravel 11 |
| 语言 | PHP ^8.2 |
| 数据库 | MySQL |
| 前端 | Blade 模板 + 原生 CSS |
| 富文本编辑器 | TinyMCE 6 |
| Excel 导入导出 | Laravel Excel (maatwebsite/excel) |

## 安装部署

### 环境要求

- PHP >= 8.2
- Composer
- MySQL 5.7+ / MariaDB 10.3+
- 扩展：BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

### 安装步骤

```bash
# 1. 进入项目目录
cd mcq-practice

# 2. 安装 PHP 依赖
composer install --no-dev --optimize-autoloader

# 3. 复制环境配置并生成 APP_KEY
cp .env.example .env
php artisan key:generate

# 4. 编辑 .env 配置数据库连接
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. 创建数据库（如未创建）
mysql -u root -p -e "CREATE DATABASE mcq_practice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. 运行迁移
php artisan migrate

# 7. 创建存储链接（用于编辑器图片上传）
php artisan storage:link

# 8. 设置目录权限（Linux 环境）
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/storage

# 9. 创建初始用户和演示数据（二选一）

## 方式一：运行种子数据（推荐）
php artisan db:seed

# 种子数据会创建以下账号（密码均为 password）：
#   superadmin — super_admin 角色（超级管理员）
#   admin      — admin 角色（管理员）
#   student    — student 角色（已通过审核的学员）
#   pendingstudent — student 角色（待审核的学员）
# 同时创建示例分类（PHP 基础、MySQL 基础）和演示题目

## 方式二：手动创建 super_admin 账号
# 若不需要种子数据，可单独创建超级管理员：
php artisan tinker --execute="\App\Models\User::create(['name'=>'Super Admin','username'=>'superadmin','email'=>'super@example.com','password'=>bcrypt('password'),'role'=>'super_admin','approval_status'=>'approved','approved_at'=>now(),'email_verified_at'=>now()]);"
```

### Web 服务器配置

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/mcq-practice/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### Apache

项目自带的 `public/.htaccess` 已配置好 URL 重写，确保 `mod_rewrite` 已启用。

### 环境变量

| 变量 | 默认值 | 说明 |
|------|--------|------|
| `APP_URL` | `http://localhost` | 应用访问地址 |
| `DB_DATABASE` | `mcq_practice` | 数据库名称 |
| `PRACTICE_QUESTIONS_PER_SESSION` | `10` | 每次练习题量（可在后台系统设置中修改） |
| `REGISTRATION_ENABLED` | `true` | 是否开启自助注册 |
| `REGISTRATION_REQUIRES_APPROVAL` | `false` | 注册是否需要审核 |
| `PAGINATION_QUESTIONS` | `20` | 题库列表每页条数 |
| `PAGINATION_USERS` | `20` | 用户列表每页条数 |
| `PAGINATION_WRONG_QUESTIONS` | `20` | 错题本每页条数 |
| `PAGINATION_ATTEMPTS` | `20` | 练习历史每页条数 |

## 默认管理员

安装后可通过 `php artisan tinker` 创建管理员账号：

```bash
php artisan tinker --execute="\App\Models\User::create(['name'=>'管理员','email'=>'admin@example.com','password'=>bcrypt('your-password'),'role'=>'admin','email_verified_at'=>now()]);"
```

## 截图

（可自行补充截图）

## License

MIT
