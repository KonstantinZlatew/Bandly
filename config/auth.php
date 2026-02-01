<?php
/**
 * Cookie-based authentication helper functions
 */

/**
 * Set a cookie with secure defaults
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param int $expire Expiration time in seconds (default: 1 week)
 */
function setAuthCookie(string $name, string $value, int $expire = 604800): void {
    $expireTime = time() + $expire;
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Strict';
    
    // PHP 7.3+ supports SameSite attribute
    if (PHP_VERSION_ID >= 70300) {
        setcookie($name, $value, [
            'expires' => $expireTime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    } else {
        // Fallback for older PHP versions
        setcookie($name, $value, $expireTime, '/', '', $secure, $httponly);
    }
}

/**
 * Get a cookie value
 * @param string $name Cookie name
 * @return string|null Cookie value or null if not set
 */
function getAuthCookie(string $name): ?string {
    return $_COOKIE[$name] ?? null;
}

/**
 * Delete a cookie
 * @param string $name Cookie name
 */
function deleteAuthCookie(string $name): void {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    if (PHP_VERSION_ID >= 70300) {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } else {
        setcookie($name, '', time() - 3600, '/', '', $secure, true);
    }
    
    // Also unset from $_COOKIE array
    unset($_COOKIE[$name]);
}

/**
 * Set user authentication cookies
 * @param int $userId User ID
 * @param string $username Username
 * @param string $email User email
 * @param int $isAdmin Admin flag (0 or 1)
 * @param string|null $profilePicUrl Profile picture URL (optional)
 */
function setUserCookies(int $userId, string $username, string $email, int $isAdmin = 0, ?string $profilePicUrl = null): void {
    setAuthCookie('user_id', (string)$userId);
    setAuthCookie('username', $username);
    setAuthCookie('email', $email);
    setAuthCookie('is_admin', (string)$isAdmin);
    
    if ($profilePicUrl !== null) {
        setAuthCookie('profile_picture_url', $profilePicUrl);
    }
}

/**
 * Clear all user authentication cookies
 */
function clearUserCookies(): void {
    deleteAuthCookie('user_id');
    deleteAuthCookie('username');
    deleteAuthCookie('email');
    deleteAuthCookie('is_admin');
    deleteAuthCookie('profile_picture_url');
}

/**
 * Check if user is authenticated
 * @return bool True if user_id cookie exists
 */
function isAuthenticated(): bool {
    return isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id']);
}

/**
 * Get authenticated user ID
 * @return int|null User ID or null if not authenticated
 */
function getUserId(): ?int {
    if (!isAuthenticated()) {
        return null;
    }
    return (int)$_COOKIE['user_id'];
}

/**
 * Get authenticated username
 * @return string|null Username or null if not authenticated
 */
function getUsername(): ?string {
    return $_COOKIE['username'] ?? null;
}

/**
 * Get authenticated user email
 * @return string|null Email or null if not authenticated
 */
function getEmail(): ?string {
    return $_COOKIE['email'] ?? null;
}

/**
 * Check if user is admin
 * @return bool True if user is admin
 */
function isAdmin(): bool {
    return isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === '1';
}

/**
 * Get profile picture URL
 * @return string|null Profile picture URL or null
 */
function getProfilePictureUrl(): ?string {
    return $_COOKIE['profile_picture_url'] ?? null;
}

/**
 * Update a specific user cookie (e.g., after profile update)
 * @param string $key Cookie key (username, email, profile_picture_url)
 * @param string $value New value
 */
function updateUserCookie(string $key, string $value): void {
    $allowedKeys = ['username', 'email', 'profile_picture_url'];
    if (in_array($key, $allowedKeys, true)) {
        setAuthCookie($key, $value);
    }
}

