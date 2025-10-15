<?php
declare(strict_types=1);

// 0) Session + config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use your actual config file & mysqli handle ($connection)
require_once __DIR__ . '/../config/config.php';

// ---- LIMIT GUARD (add near the very top) -------------------------
$__lim = __DIR__ . DIRECTORY_SEPARATOR . 'creationLimits.inc.php';
if (!is_file($__lim)) {
    // Use your usual error style if not JSON
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'SERVER_MISCONFIG: creationLimits.inc.php not found']);
    exit;
}
require_once $__lim;
// ------------------------------------------------------------------


include_once __DIR__ . '/creationLimits.inc.php';

$userId = cm_resolve_user_id($connection);
if ($userId === null) {
    // If this endpoint returns JSON:
    // http_response_code(401);
    // echo json_encode(['ok' => false, 'error' => 'NOT_AUTHENTICATED']); exit;

    // If this endpoint uses form post/redirect:
    $_SESSION['error'] = 'Please sign in first.';
    header('Location: ../quizzes.php'); exit;
}


// Minimal “logged in” check (don’t require a specific key since tbl_quizzes has no user column)
$loggedIn = !empty($_SESSION) && (isset($_SESSION['user_id']) || isset($_SESSION['username']) || isset($_SESSION['sUser']));
if (!$loggedIn) {
    echo "<script>alert('You must be logged in'); window.location.href='../login.php';</script>";
    exit;
}

// 1) Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['quizDataJson'])) {
    echo "<script>alert('Invalid request'); window.location.href='../createQuiz.php';</script>";
    exit;
}

$quizData = json_decode($_POST['quizDataJson'], true);
if (!is_array($quizData) || empty($quizData['questions']) || !is_array($quizData['questions'])) {
    echo "<script>alert('No questions to save'); window.location.href='../createQuiz.php';</script>";
    exit;
}

// 2) Helpers
function mapQuestionType(string $jsType): string {
    if ($jsType === 'multiple_choice') return 'mcq';
    if ($jsType === 'true_false')      return 'true_false';
    if ($jsType === 'fill_blank')      return 'fill_blank';
    if ($jsType === 'identification')  return 'identification';
    return 'mcq';
}

// Optional lesson_id
$lessonId = null;
if (isset($quizData['lesson_id']) && is_numeric($quizData['lesson_id'])) {
    $lessonId = (int)$quizData['lesson_id'];
}

// ✅ ADDED: Resolve user_id to be saved as author_id
$userID = null;
if (isset($_SESSION['sUserID']) && is_numeric($_SESSION['sUserID'])) {
    $userID = (int)$_SESSION['sUserID'];
} elseif (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $userID = (int)$_SESSION['user_id'];
}

/* ===== DUAL-CAP GUARD (Free plan): 2 standalone + 2 lesson-linked ===== */

// Determine if this creation is standalone (no lesson_id) or lesson-linked
$isStandalone = ($lessonId === null || $lessonId === 0);

// Read subscription
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

// 🔓 Exempt: Gakusensei bypass all quiz caps
if (strcasecmp($role, 'Gakusensei') === 0) {
    // do nothing — proceed to save without checking counts
} else if (strcasecmp($subscription, 'Premium') !== 0) {
    if ($isStandalone) {
        // Count ONLY standalone quizzes authored by this user
        $standaloneCount = 0;
        if ($stmt = $connection->prepare("
            SELECT COUNT(*) AS c
            FROM tbl_quizzes
            WHERE author_id = ? AND (lesson_id IS NULL OR lesson_id = 0)
        ")) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $standaloneCount = (int)$row['c'];
            }
            $stmt->close();
        }

        if ($standaloneCount >= 2) {
            echo "<script>alert('Free plan limit reached. You can only create 2 Standalone Quizzes.'); window.location.href='../quizzes.php';</script>";
            exit;
        }
    } else {
        // Count ONLY lesson-linked quizzes attributable to this user
        // (q.lesson_id NOT NULL) AND (q.author_id = user OR linked lesson's author_id = user)
        $linkedCount = 0;
        if ($stmt = $connection->prepare("
            SELECT COUNT(*) AS c
            FROM tbl_quizzes q
            LEFT JOIN tbl_lesson l ON q.lesson_id = l.lesson_id
            WHERE q.lesson_id IS NOT NULL
              AND (q.author_id = ? OR l.author_id = ?)
        ")) {
            $stmt->bind_param("ii", $userId, $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $linkedCount = (int)$row['c'];
            }
            $stmt->close();
        }

        if ($linkedCount >= 2) {
            echo "<script>alert('Free plan limit reached. You can only create 2 Lesson-linked Quizzes.'); window.location.href='../quizzes.php';</script>";
            exit;
        }
    }
}
/* ===== END DUAL-CAP GUARD ===== */


// 3) Transaction
mysqli_begin_transaction($connection);

try {
    // 3a) Insert quiz — now includes author_id
    if ($lessonId !== null) {
        // ✅ ADDED author_id column
        $stmtQuiz = mysqli_prepare($connection, "
            INSERT INTO tbl_quizzes (lesson_id, is_ai_generated, author_id)
            VALUES (?, 0, ?)
        ");
        if (!$stmtQuiz) throw new RuntimeException('prep quiz');
        mysqli_stmt_bind_param($stmtQuiz, "ii", $lessonId, $userID);
    } else {
        // Standalone quiz — now also save its title
        $quizTitle = '';
        if (isset($quizData['lesson_title']) && trim($quizData['lesson_title']) !== '') {
            $quizTitle = trim($quizData['lesson_title']);
        } elseif (!empty($_POST['quiz_title'])) {
            $quizTitle = trim($_POST['quiz_title']);
        }

        $stmtQuiz = mysqli_prepare($connection, "
            INSERT INTO tbl_quizzes (is_ai_generated, author_id, title)
            VALUES (0, ?, ?)
        ");
        if (!$stmtQuiz) throw new RuntimeException('prep quiz');
        mysqli_stmt_bind_param($stmtQuiz, "is", $userID, $quizTitle);
    }

    if (!mysqli_stmt_execute($stmtQuiz)) throw new RuntimeException('exec quiz');
    $quizId = (int) mysqli_insert_id($connection);

    // Mark newly created standalone quiz IDs in the session for "My Quizzes"
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['my_orphan_quizzes'])) {
        $_SESSION['my_orphan_quizzes'] = [];
    }
    $_SESSION['my_orphan_quizzes'][] = $quizId;
    $_SESSION['my_orphan_quizzes'] = array_values(array_unique(array_slice($_SESSION['my_orphan_quizzes'], -100)));
    mysqli_stmt_close($stmtQuiz);

    // 3b) Prepare question + option statements (unchanged)
    $stmtQ = mysqli_prepare(
        $connection,
        "INSERT INTO tbl_questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)"
    );
    if (!$stmtQ) throw new RuntimeException('prep question');

    $stmtOpt = mysqli_prepare(
        $connection,
        "INSERT INTO tbl_question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)"
    );
    if (!$stmtOpt) throw new RuntimeException('prep option');

    // 3c) Fan out questions/options
    foreach ($quizData['questions'] as $q) {
        $qText = trim((string)($q['question_text'] ?? ''));
        $qType = mapQuestionType((string)($q['question_type'] ?? 'multiple_choice'));

        mysqli_stmt_bind_param($stmtQ, "iss", $quizId, $qText, $qType);
        if (!mysqli_stmt_execute($stmtQ)) throw new RuntimeException('exec question');
        $questionId = (int) mysqli_insert_id($connection);

        if ($qType === 'mcq' || $qType === 'true_false') {
            $options = is_array($q['options'] ?? null) ? $q['options'] : [];
            foreach ($options as $opt) {
                $optText   = (string)($opt['option_text'] ?? '');
                $isCorrect = !empty($opt['is_correct']) ? 1 : 0;
                mysqli_stmt_bind_param($stmtOpt, "isi", $questionId, $optText, $isCorrect);
                if (!mysqli_stmt_execute($stmtOpt)) throw new RuntimeException('exec option');
            }
        } else {
            $answer = trim((string)($q['correct_answer'] ?? ''));
            $isCorrect = 1;
            mysqli_stmt_bind_param($stmtOpt, "isi", $questionId, $answer, $isCorrect);
            if (!mysqli_stmt_execute($stmtOpt)) throw new RuntimeException('exec option');
        }
    }

    mysqli_stmt_close($stmtQ);
    mysqli_stmt_close($stmtOpt);

    mysqli_commit($connection);

    // 4) Success
    echo "<script>alert('Quiz saved successfully!'); window.location.href='../quizzes.php?saved=1';</script>";
    exit;

} catch (Throwable $e) {
    mysqli_rollback($connection);
    echo "<script>alert('Save failed.'); window.location.href='../createQuiz.php?err=savefail';</script>";
    exit;
}
