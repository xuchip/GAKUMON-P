<?php
session_start();
require_once 'config/config.php'; // Database Connection

$pageTitle = 'GAKUMON — Quiz';

// Resolve user id from session
$userID = null;
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
$userID = (int)$_SESSION['user_id'];
} elseif (!empty($_SESSION['sUser'])) {
$u = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ? LIMIT 1");
$u->bind_param("s", $_SESSION['sUser']);
$u->execute();
$res = $u->get_result();
if ($res && ($row = $res->fetch_assoc())) $userID = (int)$row['user_id'];
$u->close();
}
// ✅ redirect if still not logged in
if ($userID === null) {
    header("Location: login.php");
    exit;
}

$petData = null;
   $petSql = "SELECT 
               p.pet_name,
               p.image_url,
               up.custom_name,
               up.created_at as pet_created_at,
               DATEDIFF(NOW(), up.created_at) as days_old,
               up.energy_level
            FROM tbl_user_pet up
            INNER JOIN tbl_pet p ON up.pet_id = p.pet_id
            WHERE up.user_id = $userID
            LIMIT 1";

   $petResult = $connection->query($petSql);

   if ($petResult && $petResult->num_rows > 0) {
      $petData = $petResult->fetch_assoc();
   }

// Mobile detection function
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $mobileKeywords = [
        'mobile', 'android', 'silk', 'kindle', 'blackberry', 'iphone', 'ipod',
        'ipad', 'webos', 'symbian', 'windows phone', 'phone'
    ];
    
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

$isMobile = isMobileDevice();

// MOBILE or DESKTOP includes
if ($isMobile) {
    $pageCSS = 'CSS/mobile/quizStyle.css';
    $pageJS = 'JS/mobile/quizScript.js';
} else {
    $pageCSS = 'CSS/desktop/quizStyle.css';
    $pageJS = 'JS/desktop/quizScript.js';
}

include 'include/header.php';
require_once 'config/config.php'; // Database Connection

// Detect both lesson-linked and standalone quizzes
$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$quizId   = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$isStandalone = ($lessonId <= 0 && $quizId > 0);

// If neither is provided, bail out
if (!$lessonId && !$quizId) {
    header('Location: homepage.php');
    exit;
}

// ============================================================
// 1) If this is a standalone quiz (no lesson_id)
// ============================================================
if ($isStandalone) {
    /* Fetch quiz title directly (safe even if 'title' was just added) */
    $quizTitle = 'Standalone Quiz';
    $stmtA = $connection->prepare("
        SELECT 
            COALESCE(NULLIF(title, ''), CONCAT('Standalone Quiz #', quiz_id)) AS quiz_title
        FROM tbl_quizzes 
        WHERE quiz_id = ? 
        LIMIT 1
    ");
    $stmtA->bind_param('i', $quizId);
    $stmtA->execute();
    $stmtA->bind_result($quizTitle);
    $stmtA->fetch();
    $stmtA->close();

    /* Fetch questions for this quiz */
    $questions = [];
    $stmtB = $connection->prepare("
        SELECT question_id, question_text, question_type
        FROM tbl_questions
        WHERE quiz_id = ?
        ORDER BY question_id
    ");
    $stmtB->bind_param('i', $quizId);
    $stmtB->execute();
    $resB = $stmtB->get_result();

    while ($row = $resB->fetch_assoc()) {
        $map = ['mcq'=>'multiple_choice','true_false'=>'true_false','fill_blank'=>'fill_blank','identification'=>'identification'];
        $questions[] = [
            'question_id'   => (int)$row['question_id'],
            'question_text' => $row['question_text'],
            'question_type' => $map[$row['question_type']] ?? $row['question_type'],
            'options'       => [],
        ];
    }
    $stmtB->close();

    /* Fetch options */
    if ($questions) {
        $ids = array_column($questions, 'question_id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = "SELECT question_id, option_text, is_correct
                FROM tbl_question_options
                WHERE question_id IN ($placeholders)
                ORDER BY option_id";
        $stmtC = $connection->prepare($sql);
        $stmtC->bind_param($types, ...$ids);
        $stmtC->execute();
        $resC = $stmtC->get_result();

        $byId = [];
        foreach ($questions as $i => $q) { $byId[$q['question_id']] = $i; }

        while ($opt = $resC->fetch_assoc()) {
            $qid = (int)$opt['question_id'];
            $i   = $byId[$qid] ?? null;
            if ($i === null) continue;

            $qt = $questions[$i]['question_type'];
            if ($qt === 'multiple_choice' || $qt === 'true_false') {
                $questions[$i]['options'][] = [
                    'option_text' => $opt['option_text'],
                    'is_correct'  => (bool)$opt['is_correct'],
                ];
            } else {
                if ((int)$opt['is_correct'] === 1) {
                    $questions[$i]['correct_answer'] = $opt['option_text'];
                }
            }
        }
        $stmtC->close();
    }

    // Prepare data for JS
    $serverQuizData = [
        'quiz_id'      => $quizId,
        'lesson_id'    => null,
        'lesson_title' => trim($quizTitle) !== '' ? $quizTitle : "Standalone Quiz #{$quizId}",
        'questions'    => $questions
    ];

    echo '<script>window.serverQuizData = ' .
     json_encode($serverQuizData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) .
     ';</script>';
}
// ============================================================
// 2) Otherwise, proceed with the normal lesson-linked quiz logic
// ============================================================
else {
    $lessonTitle = 'Lesson';
    $stmt1 = $connection->prepare("SELECT title FROM tbl_lesson WHERE lesson_id = ?");
    $stmt1->bind_param('i', $lessonId);
    $stmt1->execute();
    $stmt1->bind_result($lessonTitle);
    $stmt1->fetch();
    $stmt1->close();

    $quizId = 0;
    $stmt2 = $connection->prepare("SELECT quiz_id FROM tbl_quizzes WHERE lesson_id = ? LIMIT 1");
    $stmt2->bind_param('i', $lessonId);
    $stmt2->execute();
    $stmt2->bind_result($quizId);
    $stmt2->fetch();
    $stmt2->close();

    $questions = [];
    if ($quizId) {
        $stmt3 = $connection->prepare("
            SELECT question_id, question_text, question_type
            FROM tbl_questions
            WHERE quiz_id = ?
            ORDER BY question_id
        ");
        $stmt3->bind_param('i', $quizId);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while ($row = $res3->fetch_assoc()) {
            $map = ['mcq'=>'multiple_choice','true_false'=>'true_false','fill_blank'=>'fill_blank','identification'=>'identification'];
            $questions[] = [
                'question_id'   => (int)$row['question_id'],
                'question_text' => $row['question_text'],
                'question_type' => $map[$row['question_type']] ?? $row['question_type'],
                'options'       => [],
            ];
        }
        $stmt3->close();

        if ($questions) {
            $ids = array_column($questions, 'question_id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));

            $sql = "SELECT question_id, option_text, is_correct
                    FROM tbl_question_options
                    WHERE question_id IN ($placeholders)
                    ORDER BY option_id";
            $stmt4 = $connection->prepare($sql);
            $stmt4->bind_param($types, ...$ids);
            $stmt4->execute();
            $res4 = $stmt4->get_result();

            $byId = [];
            foreach ($questions as $i => $q) { $byId[$q['question_id']] = $i; }

            while ($opt = $res4->fetch_assoc()) {
                $qid = (int)$opt['question_id'];
                $i   = $byId[$qid] ?? null;
                if ($i === null) continue;

                $qt = $questions[$i]['question_type'];
                if ($qt === 'multiple_choice' || $qt === 'true_false') {
                    $questions[$i]['options'][] = [
                        'option_text' => $opt['option_text'],
                        'is_correct'  => (bool)$opt['is_correct'],
                    ];
                } else {
                    if ((int)$opt['is_correct'] === 1) {
                        $questions[$i]['correct_answer'] = $opt['option_text'];
                    }
                }
            }
            $stmt4->close();
        }
    }

    $serverQuizData = [
        'lesson_id'    => $lessonId,
        'lesson_title' => $lessonTitle,
        'quiz_id'      => $quizId,
        'questions'    => $questions
    ];
}

// MOBILE or DESKTOP includes
if ($isMobile) {
    include 'include/mobileNav.php';
} else {
    include 'include/desktopNav.php';
}
?>

<div class="body container-fluid page-contents">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-12">
            <div class="content-area">
                <div class="quiz-container col-12">
                    <!-- Welcome Page (initially shown) -->
                    <div id="welcome-page" class="welcome-container">
                        <div class="logo-container d-flex justify-content-center mb-4">
                            <img src="IMG/Logos/quiz_logo.png" alt="Logo" class="logo img-fluid">
                        </div>
                        
                        <div id="lesson-title" class="lesson-title">
                            <?php echo htmlspecialchars($serverQuizData['lesson_title']); ?>
                        </div>
                        <div class="lesson-description">
                            Lesson
                        </div>
                        
                        <div class="form-group row mt-4">
                            <div class="col-12 submitButton">
                                <button id="start-quiz" class="btnSubmit btn btn-lg btn-primary w-100">Let's Go!</button>
                            </div>
                        </div>
                    </div>

                    <!-- Quiz in Progress (initially hidden) -->
                    <div id="quiz-in-progress" style="display: none;">
                        <div class="header d-flex justify-content-between align-items-center">
                            <div id="headerLesson-title" class="headerLesson-title">
                                <?php echo htmlspecialchars($serverQuizData['lesson_title'] ?? 'Quiz'); ?>
                            </div>
                            <button class="exitButton" id="exit-quiz">Exit Quiz</button>
                        </div>

                        <div class="progress-bar">
                            <div id="progress" class="progress" style="width: 0%"></div>
                        </div>
                        
                        <div class="question-container">
                            <div class="question-number" id="question-number">Question 1 of 5</div>
                            <p id="question-text" class="question-text"></p>
                            
                            <form id="quiz-form">
                                <div id="options-container" class="options-container"></div>

                                <div class="form-group row mt-4">
                                    <div class="col-12 submitButton">
                                        <button type="submit" class="btnSubmit btn btn-lg btn-primary w-100">Next Question</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results Page (initially hidden) -->
                    <div id="results-page" class="results-container" style="display: none;">
                        <div class="header d-flex justify-content-between align-items-center">
                            <div id="headerLesson-title" class="headerLesson-title">
                                <?php echo htmlspecialchars($serverQuizData['lesson_title'] ?? 'Quiz Results'); ?>
                            </div>
                            <button class="exitButton" id="exit-quiz">Exit Quiz</button>
                        </div>

                        <div class="progress-bar">
                            <div id="progress" class="progress" style="width: 100%"></div>
                        </div>
                        
                        <div class="results-content row mt-4">
                            <!-- Left side with score visualization -->
                            <div class="score-visualization col-md-6">
                                <div class="score-circle-container">
                                    <svg class="score-circle" viewBox="0 0 200 200">
                                        <circle class="circle-bg" cx="100" cy="100" r="90" fill="none" stroke="#f0f0f0" stroke-width="10" />
                                        <circle class="circle-progress" cx="100" cy="100" r="90" fill="none" stroke="#811212" 
                                                stroke-width="10" stroke-linecap="round" transform="rotate(-90 100 100)" 
                                                stroke-dasharray="565.48" stroke-dashoffset="0" />
                                        <text class="score-text" x="100" y="100" dy="0.35em" text-anchor="middle" 
                                            font-family="SFpro_bold" font-size="40">0/0</text>
                                    </svg>
                                </div>
                                
                                <div class="score-stats mt-4">
                                    <div class="stat-item d-flex justify-content-between">
                                        <div class="stat-label">Correct Answers:</div>
                                        <div class="stat-score" id="correct-count">0</div>
                                    </div>
                                    <div class="stat-item d-flex justify-content-between">
                                        <div class="stat-label">Total Questions:</div>
                                        <div class="stat-score" id="total-count">0</div>
                                    </div>
                                    <div class="stat-item d-flex justify-content-between">
                                        <div class="stat-label">Percentage:</div>
                                        <div class="stat-score" id="percentage">0%</div>
                                    </div>
                                    <div class="stat-item d-flex justify-content-between">
                                        <div class="stat-label">Time Taken:</div>
                                        <div class="stat-score" id="time-taken">0s</div>
                                    </div>
                                    <div class="stat-item d-flex justify-content-between">
                                        <div class="stat-label">Gakucoins Earned:</div>
                                        <div class="stat-score" id="coins-earned">0</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right side with question results -->
                            <div class="question-results col-md-6">
                                <div class="review">Question Review</div>
                                <div id="results-container" class="results-list"></div>
                                
                                <div class="backLessons mt-4">
                                    <button class="btnSubmit btn-lg btn-primary backLessons">Back to Lesson</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  window.serverQuizData = <?= json_encode($serverQuizData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php include 'include/footer.php'; ?>