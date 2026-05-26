<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success = false;
$error = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $review = trim($_POST['review'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $curses = trim($_POST['curses'] ?? '');
    $payment = trim($_POST['payment'] ?? '');
    $status = 'Новая'; // Статус устанавливается автоматически
    $errors = [];

    if (empty($curses)) {
        $errors[] = 'Выберите вид услуги';
    }
    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date)) {
        $errors[] = 'Укажите дату и время в корректном формате';
    }
    if (empty($payment)) {
        $errors[] = 'Выберите способ оплаты';
    }

    if (empty($errors)) {
        $date = str_replace('T', ' ', $date) . ':00';
        include('db.php');
        $user_id = (int)$_SESSION['user_id'];
        $stmt = $con->prepare("INSERT INTO request (review, date, curses, payment, user_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssis', $review, $date, $curses, $payment, $user_id, $status);

        if (!$stmt->execute()) {
            $error = true;
            $error_msg = 'Ошибка: ' . htmlspecialchars($con->error);
        } else {
            $success = true;
        }
        $stmt->close();
    } else {
        $error = true;
        $error_msg = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание заявки - Водить.РФ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Кнопки навигации -->
        <div class="nav-buttons">
            <a href="index.php" class="btn-nav"> Главная</a>
            <a href="history.php" class="btn-nav"> История заявок</a>
        </div>
        
        <h1> Создание заявки</h1>

        <?php if ($success): ?>
            <div class="success-message">
                 Заявка успешно отправлена!<br><br>
                <a href="history.php">🔍 Перейти к истории моих заявок →</a>
                <br><br>
                 Спасибо, что выбрали нас!
            </div>
        <?php elseif ($error): ?>
            <div class="error-message">
                 Ошибка при отправке заявки: <?php echo htmlspecialchars($error_msg); ?><br>
                <a href="javascript:history.back()">◀ Попробовать снова</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="" id="requestForm">
            
            <label for="curses">🚤 Название курса</label>
            <select id="curses" name="curses" required>
                <option value="Вождение катеров">Вождение катеров</option>
                <option value="Вождение круизных лайнеров">Вождение круизных лайнеров</option>
                <option value="Вождение яхт">Вождение яхт</option>
               
            </select>

            <label for="date"> Когда желаете начать обучение?</label>
            <input id="date" type="datetime-local" name="date" required>

            <label for="payment"> Способ оплаты</label>
            <select id="payment" name="payment" required>
                <option value="наличные">Наличные</option>
                <option value="перевод">Переводом по номеру</option>
                <option value="карта">Банковской картой</option>
            </select>

            <label for="review"> Дополнительная информация</label>
            <textarea id="review" name="review" placeholder="Опишите ваши пожелания или комментарий..."></textarea>
             
            <button type="submit" id="submitBtn" class="btn-primary"> Отправить заявку</button>
        </form>
        <?php endif; ?>
    </div>

    <script>
        // Анимация при отправке формы
        const form = document.getElementById('requestForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Добавляем класс загрузки на кнопку
                submitBtn.classList.add('loading');
                submitBtn.textContent = 'Отправка';
            });
        }

        // Анимация при фокусе на полях
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transition = 'all 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.style.transform = 'scale(1)';
                }
            });
        });
    </script>
</body>
</html>
