<?php
require_once __DIR__ . '/../config/json_database.php';

function authenticateUser($username, $password) {
    global $jsonDb;
    
    $user = $jsonDb->selectOne('users', ['username' => $username]);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

function registerUser($userData) {
    global $jsonDb;
    
    // Check if username already exists
    $existingUser = $jsonDb->selectOne('users', ['username' => $userData['username']]);
    if ($existingUser) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email already exists
    $existingEmail = $jsonDb->selectOne('users', ['email' => $userData['email']]);
    if ($existingEmail) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Set default values
    $userData['is_admin'] = 0;
    $userData['is_verified'] = 0;
    $userData['theme'] = 'default';
    $userData['country'] = $userData['country'] ?? 'US';
    
    $userId = $jsonDb->insert('users', $userData);
    
    if ($userId) {
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $jsonDb;
    $user = $jsonDb->selectOne('users', ['id' => $_SESSION['user_id']]);
    
    return $user && $user['is_admin'] == 1;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $jsonDb;
    return $jsonDb->selectOne('users', ['id' => $_SESSION['user_id']]);
}

function updateUserProfile($userId, $data) {
    global $jsonDb;
    return $jsonDb->update('users', $data, ['id' => $userId]);
}

function changePassword($userId, $newPassword) {
    global $jsonDb;
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    return $jsonDb->update('users', ['password' => $hashedPassword], ['id' => $userId]);
}
?>