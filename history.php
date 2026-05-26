<?php
session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include('db.php');

// Код изменения отзыва
$review_success = false;
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review'], $_POST['request_id'])) {
    $review = trim($_POST['review']);
    $request_id = (int)$_POST['request_id'];

    if ($review === '') {
        $error_msg = 'Отзыв не может быть пустым';
    } else {
        $stmt = $con->prepare("UPDATE request SET review = ? WHERE id = ? AND user_id = ?");
        $user_id = (int)$_SESSION['user_id'];
        $stmt->bind_param('sii', $review, $request_id, $user_id);
        if ($stmt->execute()) {
            $review_success = true;
        } else {
            $error_msg = 'Ошибка при сохранении отзыва';
        }
        $stmt->close();
    }
}

// Код истории заявок
$user_id = (int)$_SESSION['user_id'];
$query = $con->query("SELECT * FROM request WHERE user_id=$user_id ORDER BY date DESC");
if(!$query) die('query error: ' . $con->error); 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет - история заявок</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <a href="index.php" class="btn-home">🏠 Главная</a>
            <a href="create.php" class="btn-home">➕ Новая заявка</a>
            <a href="?logout=1" class="btn-home">🚪 Выйти</a>
        </div>

        <?php if ($review_success): ?>
            <div class="success-message">✅ Отзыв успешно сохранён.</div>
        <?php elseif ($error_msg): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <h1>История заявок</h1>
        
        <?php
        $i = 0;
        if($query->num_rows == 0) {
            echo '<div class="empty-state"><p>У вас пока нет заявок.</p></div>';
        }
        while($request = $query->fetch_assoc()) {
            $i++; 
            echo '
            <div class="request">
                <h2>Заявка ' . $i . '</h2>
                <b>Дата: </b>' . htmlspecialchars($request['date']) . '<br>
                <b>Вид услуги: </b>' . htmlspecialchars($request['curses']) . '<br>
                <b>Тип оплаты: </b>' . htmlspecialchars($request['payment']) . '<br><br>
                <b>Статус: </b>' . htmlspecialchars($request['status']) . '<br>';
                
            if ($request['status'] === 'Обучение завершено') {
                echo '
                <div class="review-form">
                    <form action="" method="POST">
                        <input type="hidden" name="request_id" value="' . (int)$request['id'] . '">
                        <input type="text" name="review" placeholder="Отзыв об услуге" value="' . htmlspecialchars($request['review']) . '">
                        <button type="submit"> Оставить отзыв</button>
                    </form>
                </div>';
            }
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
