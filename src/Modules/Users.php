<?php
namespace Pkiwi\Modules;

use Pkiwi\Database\Connection;

class Users
{
    /** @var Connection|null */
    private $db;

    /**
     * @param Connection|null $db
     */
    public function __construct(Connection $db = null)
    {
        $this->db = $db;
    }

    /**
     * Register a new user
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return array ['ok' => bool, 'error' => string|null, 'user' => array|null]
     */
    public function register($username, $email, $password)
    {
        $username = trim((string)$username);
        $email = trim((string)$email);
        $password = (string)$password;

        if ($username === '' || strlen($username) < 3) {
            return ['ok' => false, 'error' => 'Username must be at least 3 characters.', 'user' => null];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['ok' => false, 'error' => 'Username may only contain letters, numbers and underscores.', 'user' => null];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email address.', 'user' => null];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Password must be at least 6 characters.', 'user' => null];
        }

        $existing = $this->db->queryOne(
            'SELECT id FROM pkiwi_users WHERE username = ? OR email = ?',
            [$username, $email]
        );
        if ($existing) {
            return ['ok' => false, 'error' => 'This username or email is already registered.', 'user' => null];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->execute(
            'INSERT INTO pkiwi_users (username, email, password, created_at) VALUES (?, ?, ?, NOW())',
            [$username, $email, $hash]
        );

        $userId = (int)$this->db->lastInsertId();
        $user = $this->userById($userId);

        return ['ok' => true, 'error' => null, 'user' => $user];
    }

    /**
     * Authenticate a user
     *
     * @param string $login
     * @param string $password
     * @return array ['ok' => bool, 'error' => string|null, 'user' => array|null]
     */
    public function login($login, $password)
    {
        $login = trim((string)$login);
        $password = (string)$password;

        $user = $this->db->queryOne(
            'SELECT * FROM pkiwi_users WHERE username = ? OR email = ?',
            [$login, $login]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'error' => 'Invalid username or password.', 'user' => null];
        }

        $this->db->execute(
            'UPDATE pkiwi_users SET last_login = NOW() WHERE id = ?',
            [$user['id']]
        );
        $user['last_login'] = date('Y-m-d H:i:s');

        return ['ok' => true, 'error' => null, 'user' => $user];
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function userById($id)
    {
        $user = $this->db->queryOne(
            'SELECT id, username, email, created_at, last_login FROM pkiwi_users WHERE id = ?',
            [(int)$id]
        );
        return $user ?: null;
    }

    /**
     * @return array|null
     */
    public function getCurrentUser()
    {
        if (empty($_SESSION['pkiwi_user_id'])) {
            return null;
        }
        return $this->userById($_SESSION['pkiwi_user_id']);
    }

    /**
     * @param array $user
     */
    public function setSession(array $user)
    {
        $_SESSION['pkiwi_user_id'] = (int)$user['id'];
    }

    public function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
