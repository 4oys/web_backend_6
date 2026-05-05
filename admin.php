<?php

require_once 'config.php';

if (isset($_GET['logout'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - Task6"');
    echo '<h1>🔐 Вы вышли из системы</h1>';
    echo '<p><a href="admin.php">Войти снова</a></p>';
    exit();
}

if (!checkAdminAuth()) {
    requestAuth();  
}

$message = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteApplication($id)) {
        $message = "✅ Анкета #{$id} успешно удалена";
    } else {
        $error = "❌ Ошибка при удалении анкеты #{$id}";
    }
}


$applications = getAllApplications();

$languageStats = getLanguageStats();

$totalStats = getTotalStats();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Задание 6</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 24px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            text-decoration: none;
            transition: 0.2s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Сообщения */
        .message {
            background: #dcfce7;
            color: #16a34a;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #16a34a;
        }
        
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .stats-card h3 {
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }
        
        .stats-card p {
            margin: 8px 0;
            font-size: 16px;
        }
        
        .stats-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .lang-stats {
            list-style: none;
        }
        
        .lang-stats li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .lang-stats .lang-name {
            font-weight: 500;
        }
        
        .lang-stats .lang-count {
            background: #667eea;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        .table-container {
            background: white;
            border-radius: 24px;
            overflow-x: auto;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s;
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2563eb;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .badge {
            background: #e5e7eb;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin: 2px;
        }
        
        @media (max-width: 768px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>
            🔐 Панель администратора
        </h1>
        <div>
            <span>👋 Здравствуйте, администратор</span>
            <a href="?logout=1" class="logout-btn" onclick="return confirm('Выйти из панели администратора?')">🚪 Выйти</a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    

    <div class="stats-container">
        <div class="stats-card">
            <h3>📊 Общая статистика</h3>
            <p>Всего пользователей: <span class="number"><?= $totalStats['total'] ?></span></p>
            <p>👨 Мужчин: <?= $totalStats['men'] ?></p>
            <p>👩 Женщин: <?= $totalStats['women'] ?></p>
        </div>

        <div class="stats-card">
            <h3>💻 Языки программирования</h3>
            <?php if (!empty($languageStats)): ?>
                <ul class="lang-stats">
                    <?php foreach ($languageStats as $lang): ?>
                        <li>
                            <span class="lang-name"><?= htmlspecialchars($lang['name']) ?></span>
                            <span class="lang-count">👥 <?= $lang['count'] ?> чел.</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Нет данных</p>
            <?php endif; ?>
        </div>
    </div>
    

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Биография</th>
                    <th>Контракт</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px;">
                            📭 Нет данных. Пользователи ещё не заполняли анкеты.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= $app['id'] ?></td>
                            <td><?= htmlspecialchars($app['fio']) ?></td>
                            <td><?= htmlspecialchars($app['phone']) ?></td>
                            <td><?= htmlspecialchars($app['email']) ?></td>
                            <td><?= $app['birth_date'] ?></td>
                            <td>
                                <?php if ($app['gender'] == 'male'): ?>
                                    👨 Мужской
                                <?php else: ?>
                                    👩 Женский
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $languages = explode(',', $app['languages'] ?? '');
                                foreach ($languages as $lang):
                                    if (trim($lang)):
                                ?>
                                    <span class="badge"><?= htmlspecialchars(trim($lang)) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </td>
                            <td><?= htmlspecialchars(mb_substr($app['biography'] ?? '', 0, 50)) ?>...</td>
                            <td><?= $app['contract_agreed'] ? '✅ Да' : '❌ Нет' ?></td>
                            <td><?= $app['created_at'] ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $app['id'] ?>" class="btn-edit">✏️ Редактировать</a>
                                <a href="?delete=<?= $app['id'] ?>" class="btn-delete" 
                                   onclick="return confirm('Удалить анкету пользователя <?= htmlspecialchars($app['fio']) ?>?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>