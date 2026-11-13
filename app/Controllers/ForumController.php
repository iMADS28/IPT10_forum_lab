<?php
declare(strict_types=1);

namespace Forum\Controllers;

use Forum\Models\Database;
use Forum\Models\User;
use Forum\Models\Discussion;
use Forum\Models\Comment;
use Forum\Security;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class ForumController
{
    private Mustache_Engine $mustache;
    private User $userModel;
    private Discussion $discussionModel;
    private Comment $commentModel;

    public function __construct()
    {
        $pdo = Database::getInstance();
        $this->userModel       = new User($pdo);
        $this->discussionModel = new Discussion($pdo);
        $this->commentModel    = new Comment($pdo);

        $this->mustache = new Mustache_Engine([
            'loader'          => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../Views'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../Views/partials'),
            'escape'          => fn($v) => Security::e($v),
        ]);
    }

    // ── Home: list discussions ───────────────────────────────────────────────

    public function home(): void
    {
        $discussions = $this->discussionModel->all();
        echo $this->mustache->render('home', [
            'discussions'  => $discussions,
            'logged_in'    => Security::isLoggedIn(),
            'username'     => Security::username(),
            'csrf_token'   => Security::csrfToken(),
        ]);
    }

    // ── Discussion + nested comment tree ────────────────────────────────────

    public function discussion(string $id): void
    {
        $discussion = $this->discussionModel->find((int) $id);
        if (!$discussion) {
            http_response_code(404);
            echo '<h1>Discussion not found.</h1>';
            return;
        }

        $flat    = $this->commentModel->allForDiscussion((int) $id);
        $tree    = $this->commentModel->buildTree($flat);
        $treeHtml = $this->renderTree($tree);

        echo $this->mustache->render('discussion', [
            'discussion'   => $discussion,
            'tree_html'    => $treeHtml,
            'logged_in'    => Security::isLoggedIn(),
            'username'     => Security::username(),
            'csrf_token'   => Security::csrfToken(),
            'discussion_id'=> (int) $id,
            'comments'     => $this->flatForSelect($flat),
        ]);
    }

    /**
     * Render the comment tree as HTML.
     * We flatten the rooted tree into pre-order traversal order,
     * so each node sits in a single list with margin-left = min(depth, 8) * 32px.
     * This cleanly caps visual indentation at depth 8 while preserving accurate depth labels.
     */
    private function renderTree(array $nodes): string
    {
        if (empty($nodes)) {
            return '<p>No comments yet. Be the first to reply!</p>';
        }
        $flat = [];
        $this->flattenTree($nodes, $flat);

        $html = '<ul class="comment-tree">';
        foreach ($flat as $node) {
            $indent = min((int) $node['depth'], 8) * 28;
            $depth  = (int) $node['depth'];
            $body   = Security::e($node['body']);
            $author = Security::e($node['username']);
            $date   = Security::e($node['created_at']);
            $id     = (int) $node['id'];

            $html .= '<li class="comment" style="margin-left:' . $indent . 'px" data-depth="' . $depth . '" id="comment-' . $id . '">';
            $html .= '<div class="comment-meta">';
            $html .= '<span class="avatar" aria-hidden="true">👤</span> ';
            $html .= '<span class="author">' . $author . '</span> ';
            $html .= '<span class="depth-badge">depth ' . $depth . '</span> ';
            $html .= '&bull; <time>' . $date . '</time>';
            $html .= '</div>';
            $html .= '<div class="comment-body">' . nl2br($body) . '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private function flattenTree(array $nodes, array &$flat): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            $copy = $node;
            unset($copy['children']);
            $flat[] = $copy;
            if (!empty($children)) {
                $this->flattenTree($children, $flat);
            }
        }
    }

    private function flatForSelect(array $flat): array
    {
        $result = [];
        foreach ($flat as $c) {
            $result[] = [
                'id'    => $c['id'],
                'label' => '#' . $c['id'] . ' — ' . mb_substr($c['body'], 0, 60),
            ];
        }
        return $result;
    }

    // ── Register ─────────────────────────────────────────────────────────────

    public function registerForm(): void
    {
        echo $this->mustache->render('register', [
            'csrf_token' => Security::csrfToken(),
            'logged_in'  => Security::isLoggedIn(),
            'username'   => Security::username(),
            'error'      => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_error']);
    }

    public function register(): void
    {
        $token    = $_POST['csrf_token'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!Security::verifyCsrf($token)) {
            http_response_code(403);
            echo 'Invalid CSRF token.';
            return;
        }

        // Validation
        $errors = [];
        if (strlen($username) < 2 || strlen($username) > 50) {
            $errors[] = 'Username must be 2–50 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', $errors);
            header('Location: /forum/register');
            exit;
        }

        // Check uniqueness
        if ($this->userModel->findByEmail($email)) {
            $_SESSION['flash_error'] = 'Email already in use.';
            header('Location: /forum/register');
            exit;
        }
        if ($this->userModel->findByUsername($username)) {
            $_SESSION['flash_error'] = 'Username already taken.';
            header('Location: /forum/register');
            exit;
        }

        $userId = $this->userModel->create($username, $email, $password);

        // Log in after registration
        session_regenerate_id(true);
        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;
        header('Location: /forum/');
        exit;
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        echo $this->mustache->render('login', [
            'csrf_token' => Security::csrfToken(),
            'logged_in'  => Security::isLoggedIn(),
            'username'   => Security::username(),
            'error'      => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_error']);
    }

    public function login(): void
    {
        $token    = $_POST['csrf_token'] ?? '';
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!Security::verifyCsrf($token)) {
            http_response_code(403);
            echo 'Invalid CSRF token.';
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid email or password.';
            header('Location: /forum/login');
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: /forum/');
        exit;
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrf($token)) {
            http_response_code(403);
            echo 'Invalid CSRF token.';
            return;
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: /forum/');
        exit;
    }

    // ── Post comment ─────────────────────────────────────────────────────────

    public function postComment(string $discussionId): void
    {
        if (!Security::isLoggedIn()) {
            http_response_code(401);
            echo 'Authentication required. Please log in first.';
            return;
        }

        $discussion = $this->discussionModel->find((int) $discussionId);
        if (!$discussion) {
            http_response_code(404);
            echo 'Discussion not found.';
            return;
        }

        $token        = $_POST['csrf_token'] ?? '';
        $body         = trim($_POST['body'] ?? '');
        $parentIdRaw  = $_POST['parent_id'] ?? '';
        $parentId     = ($parentIdRaw !== '' && ctype_digit($parentIdRaw))
                        ? (int) $parentIdRaw
                        : null;

        if (!Security::verifyCsrf($token)) {
            http_response_code(403);
            echo 'Invalid CSRF token.';
            return;
        }

        if (strlen($body) < 1) {
            header('Location: /forum/discussions/' . $discussionId);
            exit;
        }

        try {
            $this->commentModel->create(
                Security::userId(),
                (int) $discussionId,
                $parentId,
                $body
            );
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo Security::e($e->getMessage());
            return;
        }

        header('Location: /forum/discussions/' . $discussionId);
        exit;
    }
}

