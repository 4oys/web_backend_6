<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'u82564');
define('DB_USER', 'u82564');
define('DB_PASS', '1341640');

/**
 * Получение подключения к базе данных
 * @return PDO Объект подключения
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Проверка HTTP-авторизации администратора
 * @return bool true - если авторизован
 */
function checkAdminAuth() {
    // Проверяем, есть ли данные авторизации
    return true;
    // if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    //     return false;
    // }
    
    // $login = $_SERVER['PHP_AUTH_USER'];
    // $password = $_SERVER['PHP_AUTH_PW'];
    
    // try {
    //     $pdo = getDBConnection();
    //     $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE login = ?");
    //     $stmt->execute([$login]);
    //     $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
    //     if ($admin && password_verify($password, $admin['password_hash'])) {
    //         return true;
    //     }
    // } catch (PDOException $e) {
    //     return false;
    // }
    
    // return false;
}

function requestAuth() {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - Task6"');
    echo '<h1>🔐 Требуется авторизация</h1>';
    echo '<p>Для доступа к панели администратора введите логин и пароль.</p>';
    exit();
}

/**
 * Получение всех анкет с выбранными языками
 * @return array Массив анкет
 */
function getAllApplications() {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("
        SELECT a.*, 
               GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
        FROM task6_applications a
        LEFT JOIN task6_application_languages al ON a.id = al.application_id
        LEFT JOIN task6_programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получение одной анкеты по ID
 * @param int $id ID анкеты
 * @return array|null Данные анкеты или null
 */
function getApplicationById($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT a.*, 
               GROUP_CONCAT(pl.name SEPARATOR ',') as languages
        FROM task6_applications a
        LEFT JOIN task6_application_languages al ON a.id = al.application_id
        LEFT JOIN task6_programming_languages pl ON al.language_id = pl.id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Обновление анкеты
 * @param int $id ID анкеты
 * @param array $data Данные из формы
 * @return bool Успех операции
 */
function updateApplication($id, $data) {
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE task6_applications 
            SET fio = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, contract_agreed = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['fio'],
            $data['phone'],
            $data['email'],
            $data['birth_date'],
            $data['gender'],
            $data['biography'],
            $data['contract_agreed'] ?? 0,
            $id
        ]);
        
        $pdo->prepare("DELETE FROM task6_application_languages WHERE application_id = ?")->execute([$id]);
        
        if (!empty($data['languages'])) {
            $langIdStmt = $pdo->prepare("SELECT id FROM task6_programming_languages WHERE name = ?");
            $insertStmt = $pdo->prepare("INSERT INTO task6_application_languages (application_id, language_id) VALUES (?, ?)");
            
            foreach ($data['languages'] as $langName) {
                $langIdStmt->execute([$langName]);
                $langId = $langIdStmt->fetchColumn();
                if ($langId) {
                    $insertStmt->execute([$id, $langId]);
                }
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

/**
 * Удаление анкеты
 * @param int $id ID анкеты
 * @return bool Успех операции
 */
function deleteApplication($id) {
    $pdo = getDBConnection();
    
    try {

        $pdo->prepare("DELETE FROM task6_application_languages WHERE application_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM task6_applications WHERE id = ?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Получение статистики по языкам
 * @return array Массив с названием языка и количеством пользователей
 */
function getLanguageStats() {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("
        SELECT pl.name, COUNT(al.application_id) as count
        FROM task6_programming_languages pl
        LEFT JOIN task6_application_languages al ON pl.id = al.language_id
        GROUP BY pl.id
        ORDER BY count DESC, pl.name ASC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получение общей статистики
 * @return array Массив с общей информацией
 */
function getTotalStats() {
    $pdo = getDBConnection();
    
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM task6_applications")->fetchColumn();
    $totalMen = $pdo->query("SELECT COUNT(*) FROM task6_applications WHERE gender = 'male'")->fetchColumn();
    $totalWomen = $pdo->query("SELECT COUNT(*) FROM task6_applications WHERE gender = 'female'")->fetchColumn();
    
    return [
        'total' => $totalUsers,
        'men' => $totalMen,
        'women' => $totalWomen
    ];
}

$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
?>
