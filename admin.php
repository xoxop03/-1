<?php
session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
include('db.php');
if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header('HTTP/1.1 403 Forbidden');
    die('Чтобы посмотреть панель администратора, надо войти в его аккаунт.');
}

// Обработка изменения статуса заявки
$status_updated = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'] ?? '';
    $allowed_statuses = ['Новая', 'Идет обучение', 'Обучение завершено'];

    if (!in_array($status, $allowed_statuses, true)) {
        die('Недопустимый статус заявки');
    }

    $stmt = $con->prepare("UPDATE request SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $request_id);
    if (!$stmt->execute()) {
        die('update error: ' . $con->error);
    }
    $status_updated = true;
    $stmt->close();
}

// Получение всех заявок с данными пользователей
$query = $con->query("SELECT request.*, users.login, users.fullname 
                      FROM request 
                      INNER JOIN users ON request.user_id = users.id
                      ORDER BY request.date DESC");
if (!$query) die('query error: ' . $con->error);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель Администратора - Водить.РФ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav-buttons">
            <a href="index.php" class="btn-nav">🏠 Главная</a>
            <a href="?logout=1" class="btn-nav" onclick="return confirm('Выйти из аккаунта?')">🚪 Выход</a>
        </div>

        <div class="header">
            <h1>👨‍💼 Панель администратора</h1>
            <p class="subtitle">Управление заявками пользователей</p>
        </div>

        <?php
        // Подсчет статистики
        $total_requests = $query->num_rows;
        $new_requests = 0;
        $in_progress = 0;
        $completed = 0;
        
        // Временное сохранение результатов для подсчета
        $requests_data = [];
        while ($row = $query->fetch_assoc()) {
            $requests_data[] = $row;
            switch ($row['status']) {
                case 'Новая': $new_requests++; break;
                case 'Идет обучение': $in_progress++; break;
                case 'Обучение завершено': $completed++; break;
            }
        }
        ?>

        <!-- Статистика -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $total_requests ?></div>
                <div class="stat-label"> Всего заявок</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?= $new_requests ?></div>
                <div class="stat-label"> Новые</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #17a2b8;"><?= $in_progress ?></div>
                <div class="stat-label"> В обучении</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?= $completed ?></div>
                <div class="stat-label">✅ Завершено</div>
            </div>
        </div>

        <?php if (empty($requests_data)): ?>
            <div class="empty-state">
                <h3>📭 Пока нет заявок</h3>
                <p>Когда пользователи оставят заявки, они появятся здесь</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests_data as $index => $request): ?>
                <div class="request-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            👤 <?= htmlspecialchars($request['login']) ?>
                        </h2>
                        <span class="card-number">Заявка №<?= $index + 1 ?></span>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"> ФИО</div>
                            <div class="info-value"><?= htmlspecialchars($request['fullname']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"> Дата</div>
                            <div class="info-value"><?= htmlspecialchars($request['date']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"> Услуга</div>
                            <div class="info-value"><?= htmlspecialchars($request['curses']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"> Оплата</div>
                            <div class="info-value"><?= htmlspecialchars($request['payment']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"> Комментарий</div>
                            <div class="info-value"><?= htmlspecialchars($request['review']) ?: '—' ?></div>
                        </div>
                    </div>

                    <div class="status-form">
                        <form action="" method="POST" class="status-update-form">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <label>📊 Изменить статус</label>
                            <select name="status" class="status-select">
                                <option <?= $request['status'] == 'Новая' ? 'selected' : '' ?> value="Новая">🆕 Новая</option>
                                <option <?= $request['status'] == 'Идет обучение' ? 'selected' : '' ?> value="Идет обучение">📖 Идет обучение</option>
                                <option <?= $request['status'] == 'Обучение завершено' ? 'selected' : '' ?> value="Обучение завершено">✅ Обучение завершено</option>
                            </select>
                            <button type="submit" class="btn-save">💾 Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Уведомление об успешном обновлении статуса
        <?php if ($status_updated): ?>
            const notification = document.createElement('div');
            notification.className = 'success-notification';
            notification.innerHTML = '✅ Статус заявки успешно обновлен!';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        <?php endif; ?>

        // Анимация при отправке формы
        const forms = document.querySelectorAll('.status-update-form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('.btn-save');
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Сохранение...';
                button.style.opacity = '0.7';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.opacity = '1';
                }, 2000);
            });
        });

        // Плавное появление карточек при прокрутке
        const cards = document.querySelectorAll('.request-card');
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateX(0)';
                }
            });
        }, observerOptions);
        
        cards.forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
