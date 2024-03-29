<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/validator.php';
include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../database/database_connection.php';
$connect = connect();

//1.Проверить метод HTTP;
//2.Валидация данных;
//3.Регистрация.
//4.Аутентификация.

//1.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessages('Method not allowed!', 'warnings');
    header('Location: ' . HOME_PAGE);
    exit;
}

//Set values from form into session
setValues('register_form', $_POST);

// Filtered POST method
$filteredPost = filterPost($_POST);

//2.
$passwordHash = password_hash($filteredPost['password'], PASSWORD_BCRYPT);

// все что приходит от пользователя всегда нужно фильтровать
$errors = validate($filteredPost, [
    'name' => 'required|min_length[5]',
    'email' => 'required|email|min_length[6]',
    'password' => 'required|min_length[5]|max_length[255]|password|confirm',
]);

if ($errors) {
    setValidationErrors($errors);
    header('Location: ' . HOME_PAGE);
    exit;
}

//3.Registration
//Check if user exist
if (checkUserExist($connect, post('email', 'email'))) {
    $errors['email'][] = 'This email is already taken!';
    setValidationErrors($errors);
    header('Location: ' . HOME_PAGE . ' ');
    exit;
}

//Регистрируем пользователя и сохраняем данные в БД
$userData = [
    'name' => post('name'),
    'email' => post('email', 'email'),
    'password' => $passwordHash,
    'role_id' => 2,
];

$userId = registrationUser($connect, $userData);
if (!$userId) {
    setMessages('Data Base Error!', 'warnings');
    header('Location: ' . HOME_PAGE );
    exit;
}

$token = generateToken($userId);
$sessionData = [
    'user_id' => $userId,
    'token' => $token,
    'user_agent' => getUserAgent(),
    'ip' =>getUserIp(),
];

$sessionId = createSession($connect, $sessionData);
if (!$sessionId) {
    setMessages('Data Base Error!', 'warnings');
    header('Location: ' . HOME_PAGE );
    exit;
}

setcookie('auth', $token, time() + (3600 * 24 * 7), '/');
header('Location: ' . HOME_PAGE . 'closed_page.php');







