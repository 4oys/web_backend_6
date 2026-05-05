<?php

require_once 'config.php';

if (!checkAdminAuth()) {
    requestAuth();
}


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin.php');
    exit();
}


$application = getApplicationById($id);
if (!$application) {
    header('Location: admin.php');
    exit();
}

$message = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $errors = [];
    
    $fio = trim($_POST['fio'] ?? '');
    if (empty($fio)) {
        $errors['fio'] = 'ФИО обязательно для заполнения';
    } elseif (strlen($fio) > 150) {
        $errors['fio'] = 'ФИО не должно превышать 150 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $fio)) {
        $errors['fio'] = 'ФИО содержит недопустимые символы';
    }
    
    $phone = trim($_POST['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX';
    }
    
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    }
    
    $birth_date = $_POST['birth_date'] ?? '';
    if (empty($birth_date)) {
        $errors['birth_date'] = 'Дата рождения обязательна';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        $today = new DateTime();
        if (!$date || $date > $today) {
            $errors['birth_date'] = 'Введите корректную дату';
        }
    }
    
    $gender = $_POST['gender'] ?? '';
    if (empty($gender)) {
        $errors['gender'] = 'Укажите пол';
    } elseif (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = 'Недопустимый пол';
    }
    
    $languages = $_POST['languages'] ?? [];
    if (empty($languages)) {
        $errors['languages'] = 'Выберите хотя бы один язык';
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowedLanguages)) {
                $errors['languages'] = 'Выбран недопустимый язык';
                break;
            }
        }
    }
    
    $biography = trim($_POST['biography'] ?? '');
    if (strlen($biography) > 1000) {
        $errors['biography'] = 'Биография не более 1000 символов';
    }
    
    $contract_agreed = isset($_POST['contract_agreed']) ? 1 : 0;
    
    if (empty($errors)) {
        $data = [
            'fio' => $fio,
            'phone' => $phone,
            'email' => $email,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'biography' => $biography,
            'contract_agreed' => $contract_agreed,
            'languages' => $languages
        ];
        
        if (updateApplication($id, $data)) {
            $message = "✅ Анкета #{$id} успешно обновлена";
            // Обновляем данные для отображения
            $application = getApplicationById($id);
            // Разбираем языки обратно в массив
            $application['languages_array'] = explode(',', $application['languages'] ?? '');
        } else {
            $error = "❌ Ошибка при обновлении анкеты";
        }
    } else {
        $error = implode('<br>', $errors);
        $application['fio'] = $fio;
        $application['phone'] = $phone;
        $application['email'] = $email;
        $application['birth_date'] = $birth_date;
        $application['gender'] = $gender;
        $application['biography'] = $biography;
        $application['contract_agreed'] = $contract_agreed;
        $application['languages_array'] = $languages;
    }
}

if (!isset($application['languages_array'])) {
    $application['languages_array'] = explode(',', $application['languages'] ?? '');
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование анкеты #<?= $id ?> - Админ панель</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.3);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1f2937;
        }
        .required::after {
            content: " *";
            color: #ef4444;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        select[multiple] {
            height: 150px;
        }
        .radio-group {
            display: flex;
            gap: 24px;
            padding: 8px 0;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-save, .btn-cancel {
            padding: 12px 24px;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 12px;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-cancel {
            background: #e5e7eb;
            color: #1f2937;
            text-decoration: none;
            display: inline-block;
        }
        .message {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .small-hint {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✏️ Редактирование анкеты #<?= $id ?></h1>
        <p>Пользователь: <?= htmlspecialchars($application['fio']) ?></p>
    </div>
    
    <div class="card">
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="fio" class="required">ФИО</label>
                <input type="text" id="fio" name="fio" 
                       value="<?= htmlspecialchars($application['fio']) ?>"
                       placeholder="Иванов Иван Иванович">
            </div>

            <div class="form-group">
                <label for="phone" class="required">Телефон</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= htmlspecialchars($application['phone']) ?>"
                       placeholder="+79123456789">
            </div>

            <div class="form-group">
                <label for="email" class="required">E-mail</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($application['email']) ?>"
                       placeholder="ivanov@example.com">
            </div>
            
            <div class="form-group">
                <label for="birth_date" class="required">Дата рождения</label>
                <input type="date" id="birth_date" name="birth_date" 
                       value="<?= $application['birth_date'] ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Пол</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="gender" value="male" 
                               <?= $application['gender'] == 'male' ? 'checked' : '' ?>> Мужской
                    </label>
                    <label>
                        <input type="radio" name="gender" value="female" 
                               <?= $application['gender'] == 'female' ? 'checked' : '' ?>> Женский
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="languages" class="required">Любимый язык программирования</label>
                <select name="languages[]" id="languages" multiple size="6">
                    <?php foreach ($allowedLanguages as $lang): ?>
                        <option value="<?= $lang ?>" 
                            <?= in_array($lang, $application['languages_array']) ? 'selected' : '' ?>>
                            <?= $lang ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small-hint">Удерживайте Ctrl (Cmd) для выбора нескольких языков</div>
            </div>
            
            <!-- Биография -->
            <div class="form-group">
                <label for="biography">Биография</label>
                <textarea id="biography" name="biography" rows="5" 
                          placeholder="Расскажите немного о себе..."><?= htmlspecialchars($application['biography'] ?? '') ?></textarea>
            </div>
            
            <!-- Контракт -->
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="contract_agreed" id="contract_agreed" value="1"
                           <?= $application['contract_agreed'] ? 'checked' : '' ?>>
                    <label for="contract_agreed">Я ознакомлен(а) с условиями контракта</label>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <button type="submit" class="btn-save">💾 Сохранить изменения</button>
                <a href="admin.php" class="btn-cancel">← Отмена</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>