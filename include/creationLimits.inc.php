<?php
// include/creationLimits.inc.php
// Tiny validator for Free-plan creation limits (2 lessons, 2 quizzes)

declare(strict_types=1);

/**
 * Resolve current user id from session using your existing schema.
 * Returns int|null
 */
function cm_resolve_user_id(mysqli $connection): ?int {
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    if (!empty($_SESSION['sUser'])) {
        $sql = "SELECT user_id FROM tbl_user WHERE username = ? LIMIT 1";
        if ($stmt = $connection->prepare($sql)) {
            $stmt->bind_param("s", $_SESSION['sUser']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $stmt->close();
                return (int)$row['user_id'];
            }
            $stmt->close();
        }
    }
    return null;
}

/**
 * Check if user can create another item for the given type.
 * @param mysqli $connection
 * @param int    $userId
 * @param string $type 'lesson' | 'quiz'
 * @param int    $freeLimit default 2
 * @return array{allowed:bool, remaining:int, reason?:string}
 */
function cm_check_creation_limit(mysqli $connection, int $userId, string $type, int $freeLimit = 2): array {
    // 1) Read subscription_type AND role
    $subscription = 'Free';
    $role = 'Gakusei';
    if ($stmt = $connection->prepare("SELECT subscription_type, role FROM tbl_user WHERE user_id = ? LIMIT 1")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $subscription = (string)$row['subscription_type'];
            $role = (string)$row['role'];
        }
        $stmt->close();
    }

    // 👇 NEW: Gakusensei bypass
    if (strcasecmp($role, 'Gakusensei') === 0) {
        return ['allowed' => true, 'remaining' => PHP_INT_MAX];
    }

    // Premium? always allowed.
    if (strcasecmp($subscription, 'Premium') === 0) {
        return ['allowed' => true, 'remaining' => PHP_INT_MAX];
    }

    $type = strtolower(trim($type));
    if ($type !== 'lesson' && $type !== 'quiz') {
        return ['allowed' => false, 'remaining' => 0, 'reason' => 'Invalid type'];
    }

    // 2) Count authored items
    if ($type === 'lesson') {
        $sql = "SELECT COUNT(*) AS c FROM tbl_lesson WHERE author_id = ?";
    } else { // quiz
        // Count only quizzes you authored (standalone). Lesson-linked ones that have NULL author_id won’t count.
        $sql = "SELECT COUNT(*) AS c FROM tbl_quizzes WHERE author_id = ?";
    }

    $count = 0;
    if ($stmt = $connection->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $count = (int)$row['c'];
        }
        $stmt->close();
    }

    $remaining = max(0, $freeLimit - $count);
    if ($remaining <= 0) {
        $label = $type === 'lesson' ? 'lessons' : 'quizzes';
        return [
            'allowed'   => false,
            'remaining' => 0,
            'reason'    => "Free plan limit reached. You can only create {$freeLimit} {$label} on the Free plan."
        ];
    }

    return ['allowed' => true, 'remaining' => $remaining];
}
