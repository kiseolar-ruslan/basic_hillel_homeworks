<?php

/**
 * Check user exist
 * @param PDO $connect
 * @param string $email
 * @return int
 */
function checkUserExist(PDO $connect, string $email): int
{
    $query = "SELECT count(`id`) FROM `users` WHERE `email` = ?";
//подготавливаем запрос
    $st = $connect->prepare($query);
//выполняем подготовленный запрос и по ключу 'email' передаем почту юзера в запрос
    $st->execute([$email]);
//вытягиваем результат запроса с БД
    return $st->fetchColumn();
}

/**
 * get user password
 * @param PDO $connect
 * @return string
 */
function getUserPassword(PDO $connect): string
{
    $query = "SELECT `password` FROM `users` WHERE `email` = :email";
    $st = $connect->prepare($query);
    $st->execute([
        'email' => $_POST['email'],
    ]);
    return $st->fetchColumn();
}

/**
 * Registration user
 * Try - Выполняется если ошибок нет
 * Catch - Выполняется если ошибки есть
 * try, catch - Отлавливание ошибок
 * используем при работе с исключениями, когда нужно отловить ошибки
 * Finally - Выполняется в любом случае
 * @param PDO $connect
 * @param $data
 * @return bool|int|string
 */
function registrationUser(PDO $connect, $data): bool|int|string
{
    try {
        $queryDataUser = "INSERT INTO `users` (`name`, `email`,`password`, `role_id`) VALUES (:name, :email, :password, :role_id)";
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute($data);
        return $connect->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Для создания токена при регистрации пользователя и проверки на регистрацию
 * @param PDO $connect
 * @param $data
 * @return int|bool
 */
function createSession(PDO $connect, $data): int|bool
{
    try {
        $queryDataUser = "INSERT INTO `users_sessions` (`user_id`, `token`,`user_agent`, `ip`) 
                                VALUES (:user_id, :token, :user_agent, :ip)";
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute($data);
        return $connect->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check authentication
 * @return bool|int
 */
function checkAuth(): bool|int
{
    $token = $_COOKIE['auth'] ?? false;
    if (!$token) {
        return false;
    }

    require_once __DIR__ . '/../database/database_connection.php';
    $connect = connect();
    $session = getSession($connect, $token);

    if (!$session) {
        return false;
    }

    return $session['user_id'];
}

/**
 * get session info by auth token
 * Получение с БД записи сессии по токену, который хранится в cookie
 * @param PDO $connect
 * @param string $token
 * @return array|bool
 */
function getSession(PDO $connect, string $token): array|bool
{
    try {
        $queryDataUser = "SELECT * FROM `users_sessions` WHERE `token` = ? LIMIT 1";
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute([$token]);
        return $stDataUser->fetch();
    } catch (PDOException $e) {
        logger(serialize($e), 'errors.txt', false);
        return false;
    }
}

/**
 * @param PDO $connect
 * @param string $email
 * @return array|bool
 */
function getUserByEmail(PDO $connect, string $email): array|bool
{
    try {
        $queryDataUser = "SELECT `id`, `email`, `password` FROM `users` WHERE `email` = ? LIMIT 1";
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute([$email]);
        return $stDataUser->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * login user
 * @param PDO $connect
 * @param int $userId
 * @return void
 */
function login(PDO $connect, int $userId): void
{
    $token = generateToken($userId);
    $sessionData = [
        'user_id' => $userId,
        'token' => $token,
        'user_agent' => getUserAgent(),
        'ip' => getUserIp(),
    ];

    $sessionId = createSession($connect, $sessionData);
    if (!$sessionId) {
        setMessages('Data Base Error!', 'warnings');
        header('Location: ' . HOME_PAGE . 'login_page.php');
        exit;
    }

    setcookie('auth', $token, time() + (3600 * 24 * 7), '/');
}

/**
 * @param PDO $connect
 * @return array|bool
 */
function getAllUsers(PDO $connect): array|bool
{
    try {
        $queryDataUser = "SELECT `id`, `name` FROM `users`";
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute();
        return $stDataUser->fetchAll();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * @param PDO $connect
 * @param int|bool $offset
 * @param int|bool $perPage
 * @return array|bool
 */
function getAllBlogs(PDO $connect, int|bool $offset = false, int|bool $perPage = false): array|bool
{
    try {
        $queryDataUser = "SELECT * FROM `blogs`";
        if ( $offset !== false && $perPage !== false) {
            $queryDataUser .= " LIMIT $offset, $perPage ";
        }
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute();
        return $stDataUser->fetchAll();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * @param PDO $connect
 * @return int|bool
 */
function countAllBlogs(PDO $connect): int|bool
{
    try {
        $queryDataUser = "SELECT count(`id`) as counter FROM `blogs`";
        $stDataUser = $connect->prepare($queryDataUser);
        $stDataUser->execute();
        return $stDataUser->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}