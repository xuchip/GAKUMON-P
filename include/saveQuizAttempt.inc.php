<?php
// include/saveQuizAttempt.inc.php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../config/config.php'; // provides $connection (mysqli)

try {
    // ---- Resolve current user ----
    $userId = null;

    // If your app sets user_id directly in session:
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];
    }

    // If your app uses sUser (username) in session (fallback):
    if ($userId === null && !empty($_SESSION['sUser'])) {
        $username = $_SESSION['sUser'];
        $u = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ? LIMIT 1");
        if (!$u) throw new Exception("Prepare failed (user lookup): " . $connection->error);
        $u->bind_param("s", $username);
        $u->execute();
        $res = $u->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $userId = (int) $row['user_id'];
        }
        $u->close();
    }

    if ($userId === null) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Not authenticated.']);
        exit;
    }

    // ---- Read JSON body ----
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON body.']);
        exit;
    }

    $quizId = isset($data['quiz_id']) ? (int)$data['quiz_id'] : 0;
    $score  = isset($data['score'])   ? (int)$data['score']   : null;
    $total  = isset($data['total'])   ? (int)$data['total']   : 0;    // optional, for coin rules

    if ($quizId <= 0 || $score === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing required fields: quiz_id, score.']);
        exit;
    }

    // ---- Validate quiz exists ----
    $chk = $connection->prepare('SELECT quiz_id FROM tbl_quizzes WHERE quiz_id = ? LIMIT 1');
    if (!$chk) throw new Exception("Prepare failed (quiz check): " . $connection->error);
    $chk->bind_param('i', $quizId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Quiz not found.']);
        exit;
    }
    $chk->close();

    // ---- Coins rule (adjust anytime) ----
    $gakucoinsEarned = max(0, $score); // 1 coin per correct answer

    // ---- Insert attempt ----
    $ins = $connection->prepare('
        INSERT INTO tbl_user_quiz_attempts (quiz_id, user_id, score, gakucoins_earned)
        VALUES (?, ?, ?, ?)
    ');
    if (!$ins) throw new Exception("Prepare failed (insert attempt): " . $connection->error);
    $ins->bind_param('iiii', $quizId, $userId, $score, $gakucoinsEarned);
    if (!$ins->execute()) throw new Exception("Execute failed (insert attempt): " . $ins->error);
    $attemptId = $ins->insert_id;
    $ins->close();

    // ---- (Optional) Credit user coins ----
    $upd = $connection->prepare('UPDATE tbl_user SET gakucoins = gakucoins + ? WHERE user_id = ?');
    if ($upd) {
        $upd->bind_param('ii', $gakucoinsEarned, $userId);
        $upd->execute();
        $upd->close();
    }

    // (Optional) Return new coin balance
    $newCoins = null;
    if ($q = $connection->query("SELECT gakucoins FROM tbl_user WHERE user_id = {$userId} LIMIT 1")) {
        if ($row = $q->fetch_assoc()) $newCoins = (int)$row['gakucoins'];
        $q->close();
    }

    echo json_encode([
        'ok' => true,
        'attempt_id' => $attemptId,
        'quiz_id' => $quizId,
        'user_id' => $userId,
        'score' => $score,
        'gakucoins_earned' => $gakucoinsEarned,
        'new_gakucoins' => $newCoins,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
