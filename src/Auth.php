<?php

require_once __DIR__ . '/DB.php';

class Auth
{
    /** Start the session once per request. */
    public static function boot()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Register a new user. Returns [true, userId] on success
     * or [false, errorMessage] on failure.
     */
    public static function register($name, $email, $password)
    {
        self::boot();
        $db = DB::connect();

        $name  = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || $email === '' || $password === '') {
            return [false, 'All fields are required.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Please enter a valid email address.'];
        }
        if (strlen($password) < 8) {
            return [false, 'Password must be at least 8 characters.'];
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [false, 'An account with that email already exists.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, password, plan, credits)
             VALUES (?, ?, ?, 'free', 10)"
        );
        $stmt->execute([$name, $email, $hash]);
        $userId = (int) $db->lastInsertId();

        self::setSession($userId, $name, $email);
        return [true, $userId];
    }

    /**
     * Attempt login. Returns [true, userId] or [false, errorMessage].
     */
    public static function login($email, $password)
    {
        self::boot();
        $db    = DB::connect();
        $email = strtolower(trim($email));

        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return [false, 'Invalid email or password.'];
        }

        self::setSession((int) $user['id'], $user['name'], $user['email']);
        return [true, (int) $user['id']];
    }

    public static function logout()
    {
        self::boot();
        $_SESSION = [];
        session_destroy();
    }

    public static function check()
    {
        self::boot();
        return isset($_SESSION['user_id']);
    }

    /** Redirect to login if not authenticated. */
    public static function requireLogin()
    {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    public static function id()
    {
        self::boot();
        return $_SESSION['user_id'] ?? null;
    }

    /** Fetch the full, fresh user record from the DB. */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }
        $db   = DB::connect();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([self::id()]);
        return $stmt->fetch() ?: null;
    }

    public static function credits()
    {
        $user = self::user();
        return $user ? (int) $user['credits'] : 0;
    }

    /** Deduct credits atomically. Returns true if the user had enough. */
    public static function deductCredits($amount)
    {
        $db   = DB::connect();
        $stmt = $db->prepare(
            'UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?'
        );
        $stmt->execute([$amount, self::id(), $amount]);
        return $stmt->rowCount() > 0;
    }

    public static function addCredits($userId, $amount)
    {
        $db = DB::connect();
        $db->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')
           ->execute([$amount, $userId]);
    }

    private static function setSession($id, $name, $email)
    {
        self::boot();
        session_regenerate_id(true);
        $_SESSION['user_id']    = $id;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
    }
}
