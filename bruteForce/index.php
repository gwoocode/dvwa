<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
$conn = mysqli_connect("localhost", "root", "", "dvwa_test");
$msg = "";
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

// 1. 회원가입 처리
if (isset($_POST['register'])) {
    $reg_pass = md5($_POST['reg_password']);
    if (mysqli_query($conn, "INSERT INTO `users` (user, password) VALUES ('{$_POST['reg_username']}', '$reg_pass')")) {
        $msg = "✅ 가입 완료. 로그인을 진행하세요.";
        $mode = 'login';
    }
}

// 2. 로그인 처리
if (isset($_GET['Login'])) {
    $pass_md5 = md5($_GET['password']);
    $result = mysqli_query($conn, "SELECT * FROM `users` WHERE user = '{$_GET['username']}' AND password = '$pass_md5'");

    if ($result && mysqli_num_rows($result) == 1) {
        session_regenerate_id(true);
        $_SESSION['username'] = $_GET['username'];

        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT'] ?? ''), 'python') === false) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $msg = "<pre class='error-msg'>Username and/or password incorrect.</pre>";
    }
}

// 3. 로그아웃
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. 방명록 등록
if (isset($_POST['submit_msg']) && isset($_SESSION['username'])) {
    mysqli_query($conn, "INSERT INTO guestbook (name, message) VALUES ('{$_POST['name']}', '{$_POST['message']}')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title></title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #fafafa; color: #333; margin: 40px auto; max-width: 440px; padding: 0 20px; }
        h3, h4 { margin-top: 0; font-weight: 600; color: #111; }
        .card { background: #fff; border: 1px solid #e5e7eb; padding: 24px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: #666; }
        input[type="text"], input[type="password"], textarea { width: 100%; padding: 10px; margin-bottom: 16px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { width: 100%; padding: 10px; background: #111827; color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .btn-reg { background: #4b5563; }
        .link-area { text-align: center; margin-top: 16px; font-size: 13px; }
        .link-area a { color: #4b5563; text-decoration: none; border-bottom: 1px solid #d1d5db; }
        .db-header { margin-bottom: 24px; overflow: hidden; }
        .db-header h3 { float: left; margin: 0; }
        .logout-lnk { font-size: 13px; color: #9ca3af; text-decoration: none; float: right; }
        .logout-lnk:hover { color: #dc3545; }
        .success-anchor { color: #059669; font-weight: 600; margin-bottom: 10px; }
        .error-msg { color: #dc3545; margin-bottom: 15px; }
        .msg-item { background: #f3f4f6; padding: 12px; margin-bottom: 8px; border-radius: 6px; font-size: 14px; }
        .msg-content { margin-top: 4px; color: #374151; white-space: pre-wrap; }
        
        /* 인라인 스타일 통합 부분 */
        .user-info { font-size: 14px; color: #4b5563; margin-bottom: 24px; }
        .guestbook-textarea { height: 70px; resize: none; }
        .feed-title { margin-bottom: 12px; }
        .feed-badge { color: #9ca3af; font-size: 12px; margin-left: 6px; }
    </style>
</head>
<body>

<?php if ($msg !== "") echo "<div>$msg</div>"; ?>

<?php if (!isset($_SESSION['username'])): ?>
    <div class="card">
    <?php if ($mode === 'login'): ?>
        <h3>🔒 Login</h3>
        <form method="get">
            <label>Username</label><input type="text" name="username" required>
            <label>Password</label><input type="password" name="password" required>
            <input type="submit" name="Login" value="Login" class="btn-submit">
        </form>
        <div class="link-area"><a href="?mode=register">계정이 없으신가요? 회원가입</a></div>
    <?php else: ?>
        <h3>📝 Register</h3>
        <form method="post">
            <label>New Username</label><input type="text" name="reg_username" required>
            <label>New Password</label><input type="password" name="reg_password" required>
            <input type="submit" name="register" value="Sign Up" class="btn-submit btn-reg">
        </form>
        <div class="link-area"><a href="?mode=login">이미 계정이 있습니다. 로그인</a></div>
    <?php endif; ?>
    </div>

<?php else: ?>
    <div class="db-header">
        <h3>🏠 홈</h3>
        <a href="?action=logout" class="logout-lnk">로그아웃</a>
    </div>

    <p class="success-anchor">Welcome!</p>
    <p class="user-info">사용자 계정: <b><?=htmlspecialchars($_SESSION['username'])?></b></p>

    <div class="card">
        <h4>💬 한마디 남기기</h4>
        <form method="post">
            <input type="hidden" name="name" value="<?=htmlspecialchars($_SESSION['username'])?>">
            <textarea name="message" class="guestbook-textarea" placeholder="한마디를 남겨주세요." required></textarea>
            <input type="submit" name="submit_msg" value="글쓰기" class="btn-submit">
        </form>
    </div>
    
    <h4 class="feed-title">📋 방명록</h4>
    <?php
    $gb_result = mysqli_query($conn, "SELECT * FROM guestbook ORDER BY id DESC");
    while ($row = mysqli_fetch_assoc($gb_result)):
    ?>
        <div class="msg-item">
            <b><?=htmlspecialchars($row['name'])?></b><span class="feed-badge">feed</span>
            <div class="msg-content"><?=htmlspecialchars($row['message'])?></div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<script>
window.addEventListener("pageshow", e => { if (e.persisted) location.reload(); });
</script>
</body>
</html>