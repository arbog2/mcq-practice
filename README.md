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

- PHP >= 8.2（需安装 bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml 扩展）
- Composer
- MySQL 5.7+ / MariaDB 10.3+
- 如使用图片上传功能还需：`php.ini` 中 `file_uploads = On`、`upload_max_filesize` 和 `post_max_size` 足够大

### 本地环境搭建

#### Linux（Ubuntu / Debian）

```bash
# 安装 PHP 8.2 及扩展
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-bcmath php8.2-ctype \
    php8.2-fileinfo php8.2-mbstring php8.2-xml php8.2-tokenizer php8.2-json curl unzip

# 安装 Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# 安装 MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# 安装 Git
sudo apt install -y git
```

#### Windows

> ⚠️ 以下方式仅建议用于**本地测试开发环境**，不建议在生产服务器上使用。

推荐使用 phpEnv 纯绿色集成环境。
下载地址：https://www.phpenv.cn/download.html

- 下载 phpEnv 完整版并解压
- 打开 phpEnv 内置的 CMD 或 PowerShell 窗口运行下方的安装步骤
- phpEnv 已内置 PHP、MySQL、Composer、Nginx/Apache，无需单独安装
- 下载 Git for Windows：https://git-scm.com/download/win，按照步骤安装，选择php执行文件位于 phpev

### 安装步骤

#### Linux / macOS

```bash
# 1. 克隆项目（二选一）
# 从 Gitee 克隆
git clone https://gitee.com/arbog/mcq-practice.git
# 或从 GitHub 克隆
git clone https://github.com/anomalyco/mcq-practice.git

# 2. 进入项目目录
cd mcq-practice

# 如需拉取最新代码（进入项目目录后执行）
git pull

# 3. 安装 PHP 依赖
composer install --no-dev --optimize-autoloader

# 4. 复制环境配置并生成 APP_KEY
cp .env.example .env
php artisan key:generate

# 5. 编辑 .env 配置数据库连接
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. 创建数据库（如未创建）
mysql -u root -p -e "CREATE DATABASE mcq_practice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. 运行迁移
php artisan migrate

# 8. 创建存储链接（用于编辑器图片上传）
php artisan storage:link

# 9. 设置目录权限
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/storage
```

#### Windows (PowerShell)

```powershell
# 1. 克隆项目（二选一）
# 从 Gitee 克隆
git clone https://gitee.com/arbog/mcq-practice.git
# 或从 GitHub 克隆
git clone https://github.com/anomalyco/mcq-practice.git

# 2. 进入项目目录
cd mcq-practice

# 如需拉取最新代码（进入项目目录后执行）
git pull

# 3. 安装 PHP 依赖
composer install --no-dev --optimize-autoloader

# 4. 复制环境配置并生成 APP_KEY
copy .env.example .env
php artisan key:generate

# 5. 编辑 .env 配置数据库连接
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. 创建数据库（如未创建）
mysql -u root -p -e "CREATE DATABASE mcq_practice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. 运行迁移
php artisan migrate

# 8. 创建存储链接（用于编辑器图片上传）
php artisan storage:link

# 9. 目录权限（Windows 无需设置，跳过此步）
```

#### 步骤 9（两种操作系统通用，二选一）

**方式一：运行种子数据（推荐）**  
⚠️ 执行前请先修改 `database/seeders/DatabaseSeeder.php` 中的用户名、邮箱、密码等默认值

```bash
php artisan db:seed
```

种子数据会创建以下账号（密码均为 `password`）：
- `superadmin` — `super_admin` 角色（超级管理员）
- `admin` — `admin` 角色（管理员）
- `student` — `student` 角色（已通过审核的学员）
- `pendingstudent` — `student` 角色（待审核的学员）

同时创建示例分类（PHP 基础、MySQL 基础）和演示题目。

**方式二：手动创建 `super_admin` 账号**  
⚠️ 执行前请先修改下方命令中的 `username`、`email`、`password` 等参数

```bash
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
| `PAGINATION_QUESTIONS` | `10` | 题库列表每页条数（可选 10/20/50/80/100） |
| `PAGINATION_USERS` | `20` | 用户列表每页条数 |
| `PAGINATION_WRONG_QUESTIONS` | `20` | 错题本每页条数 |
| `PAGINATION_ATTEMPTS` | `20` | 练习历史每页条数 |

## 默认管理员

安装后可通过 `php artisan tinker` 创建管理员账号：

```bash
php artisan tinker --execute="\App\Models\User::create(['name'=>'管理员','email'=>'admin@example.com','password'=>bcrypt('your-password'),'role'=>'admin','email_verified_at'=>now()]);"
```

## 欢迎加群交流

qq群号：1064744006，进群有python题库

<img src="docs/images/20260514194228_66_2.jpg" alt="交流QQ群" width="300">

## License

MIT
