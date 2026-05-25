<?php
// 1. 세션 시작 (로그인 상태 관리를 위해 최상단에 위치)
session_start();

// 2. 데이터베이스 연결
$conn = mysqli_connect("localhost", "root", "", "dvwa_test");

$login_message = "";

// 3. 로그인 처리
if (isset($_GET['Login'])) {
    $user = $_GET['username'];
    $pass = $_GET['password'];
    
    // MD5 암호화 (기존 동작 유지)
    $pass_md5 = md5($pass);
    
    // 취약한 쿼리 (SQL Injection 포인트 유지)
    $query  = "SELECT * FROM `users` WHERE user = '$user' AND password = '$pass_md5';";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        // 로그인 성공 시 세션에 유저 ID 저장하여 로그인 상태 유지
        $_SESSION['username'] = $user;
        
        // GET 요청 매개변수를 털어내기 위해 페이지 리다이렉트
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $login_message = "<p style='color: red; font-weight: bold;'>❌ Username and/or password incorrect.</p>";
    }
}

// 4. 로그아웃
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy(); // 세션 파괴
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 5. 방명록 입력 처리 (로그인된 사용자만 가능)
if (isset($_POST['submit_msg']) && isset($_SESSION['username'])) {
    $name = $_POST['name'];
    $msg = $_POST['message'];
    
    // 취약한 쿼리 (Stored XSS 및 SQLi 실습용)
    $insert_sql = "INSERT INTO guestbook (name, message) VALUES ('$name', '$msg')";
    mysqli_query($conn, $insert_sql);
    
    // 새로고침 시 중복 제출 방지 리다이렉트
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 6. 방명록 조회 (로그인된 사용자만 가져옴)
if (isset($_SESSION['username'])) {
    $select_sql = "SELECT * FROM guestbook ORDER BY id DESC";
    $result = mysqli_query($conn, $select_sql);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>놀이터</title>
    <style>
        body { margin: 40px; background-color: #f4f6f9; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        textarea.form-control { height: 100px; resize: none; }
        .btn { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: #0056b3; }
        .btn-submit { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background-color: #218838; }
        .btn-logout { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; text-decoration: none; font-size: 14px; float: right; }
        .result { margin-top: 15px; padding: 10px; border-radius: 4px; background: #eee; text-align: center; }
        .guestbook-list { margin-top: 30px; }
        .post-item { background: #fdfdfd; padding: 15px; border: 1px solid #e3e3e3; border-radius: 4px; margin-bottom: 10px; }
        .post-author { color: #007bff; font-weight: bold; }
        .post-msg { margin-top: 5px; white-space: pre-wrap; }
        .header-area { overflow: hidden; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header-area h2 { margin: 0; float: left; }
    </style>
</head>
<body>

<div class="container">

    <?php if (!isset($_SESSION['username'])): ?>
        <h2>🔒 Login</h2>
        <form method="get">
            <div class="form-group">
                <label for="username">ID:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <input type="submit" name="Login" value="Login" class="btn">
        </form>
        <?php if ($login_message !== ""): ?>
            <div class="result"><?php echo $login_message; ?></div>
        <?php endif; ?>

    <?php else: ?>
        <div class="header-area">
            <h2>📝 놀이터</h2>
            <a href="?action=logout" class="btn-logout">로그아웃</a>
        </div>
        
        <p style="color: green;">🔓 안녕하세요, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>님!</p>
        
        <form method="post">
            <div class="form-group">
                <label><b>작성자</b></label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
            </div>
            <div class="form-group">
                <label><b>내용</b></label>
                <textarea name="message" class="form-control" required></textarea>
            </div>
            <input type="submit" name="submit_msg" value="글쓰기" class="btn-submit">
        </form>
        
        <hr>

        <div class="guestbook-list">
            <h3>💬 방명록</h3>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="post-item">
                        <span class="post-author"><?php echo htmlspecialchars($row['name']); ?></span>
                        <div class="post-msg"><?php echo htmlspecialchars($row['message']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #666;">아직 작성된 방명록이 없습니다.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>