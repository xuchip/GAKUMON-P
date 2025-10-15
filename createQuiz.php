<?php
    declare(strict_types=1);
   session_start();
   require_once 'config/config.php'; // adjust path if needed

   $pageTitle = 'GAKUMON — Create Quiz';
   
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
        $pageCSS = 'CSS/mobile/createQuizStyle.css';
        $pageJS = 'JS/mobile/createQuizScript.js';
    } else {
        $pageCSS = 'CSS/desktop/createQuizStyle.css';
        $pageJS = 'JS/desktop/createQuizScript.js';
    }
   
   // Resolve current user id from session (supporting either user_id or sUser=username)
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

   include 'include/header.php';
   
   // MOBILE or DESKTOP includes
   if ($isMobile) {
        include 'include/mobileNav.php';
    } else {
        include 'include/desktopNav.php';
    }

   // --- Handle quiz creation submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture title input
    $quizTitle = trim($_POST['quiz_title'] ?? '');
    $lessonId  = isset($_POST['lesson_id']) && is_numeric($_POST['lesson_id'])
                ? (int)$_POST['lesson_id'] : 0;

    if ($lessonId > 0) {
        // Normal quiz (linked to a lesson)
        $stmt = $connection->prepare("
            INSERT INTO tbl_quizzes (lesson_id, created_by, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->bind_param('ii', $lessonId, $userID);
    } else {
        // Standalone quiz — store the title too
        $stmt = $connection->prepare("
            INSERT INTO tbl_quizzes (lesson_id, title, created_by, created_at)
            VALUES (NULL, ?, ?, NOW())
        ");
        $stmt->bind_param('si', $quizTitle, $userID);
    }

    if ($stmt->execute()) {
        $newQuizId = $stmt->insert_id;
        $stmt->close();
        header("Location: quizzes.php?created=1&id=$newQuizId");
        exit;
    } else {
        echo "<p style='color:red;text-align:center;'>Error creating quiz. Please try again.</p>";
    }
}
?>
   
<form id="quizEditorForm" action="include/createQuizSave.inc.php" method="POST">
    <!-- A hidden input to hold the final JSON string of the quiz -->
    <input type="hidden" id="quizDataJson" name="quizDataJson" value="">

    <div class="body container-fluid page-contents">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-12">
                <div class="content-area">
                    <!-- Main Content -->
                    <div class="main-content">
                        <div class="header d-flex justify-content-between align-items-center">
                            <!-- Make the lesson title editable via an input -->
                            <form id="lessonTitleForm" onsubmit="saveLessonTitle(event)">
                                <input type="text" id="headerLesson-title" class="headerLesson-title" placeholder="Enter Lesson Title" onfocus="this.select()">
                            </form>
                            <button type="button" class="exitButton" id="exit-quiz">Exit Quiz</button>
                        </div>
                        
                        <div class="editor-container">
                            <!-- Slide Preview Panel -->
                            <div class="slide-preview-panel">
                                <div class="panel-header">
                                    <button type="button" class="add-slide-btn" id="add-slide-btn">
                                        <i class="fas fa-plus"></i> &nbsp; Add Question
                                    </button>
                                </div>
                                
                                <div class="slide-list" id="slide-list">
                                    <!-- Slides will be added here dynamically -->
                                </div>
                            </div>
                            
                            <!-- Editor Area -->
                            <div class="editor-area">
                                <div class="editor-header">
                                    <div class="edit-header">Edit Question <span id="current-slide-num">1</span></div>
                                    <div class="slide-nav">
                                        <button type="button" class="nav-btn" id="prev-slide">
                                            <i class="fas fa-chevron-left"></i>Previous
                                        </button>
                                        <button type="button" class="nav-btn" id="next-slide">
                                            Next<i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="question-editor">
                                    <div class="question-type-badge" id="question-type">Multiple Choice</div>
                                    
                                    <!-- Replace contenteditable div with textarea -->
                                    <textarea 
                                        class="question-text-editor form-control" 
                                        id="question-text"
                                        name="questionText"
                                        placeholder="Enter your question here..."
                                        rows="3"
                                    >What does HTML stand for?</textarea>
                                    
                                    <div class="options-container" id="options-container">
                                        <!-- Options will be added here dynamically as inputs -->
                                    </div>
                                    
                                    <div class="editor-actions">
                                        <button type="button" class="action-btn btn-outline" id="delete-question">
                                            <i class="fas fa-trash"></i> &nbsp; Delete Question
                                        </button>
                                        <button type="button" class="action-btn btn-primary" id="save-changes">
                                            <i class="fas fa-save"></i> &nbsp; Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="footer">
                            <div class="question-number">
                                Question <span id="current-slide">1</span> of <span id="total-slides">0</span>
                            </div>
                            
                            <div class="footer-actions">
                                <!-- Change this to a submit button -->
                                <button type="submit" id="saveQuizBtn" class="btnSubmit btn-primary">
                                    Save Quiz
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
    </div>
</form> <!-- END OF FORM -->

<!-- Question Type Modal -->
<div class="modal fade" id="questionTypeModal" tabindex="-1" aria-hidden="true">
    <div class="custom-modal-backdrop"></div>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="optionTitle">Select Question Type</div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="question-type-option" data-type="multiple_choice">
                    <div class="alertTitle text-center">Multiple Choice</div>
                    <p class="alertCaption text-center mb-4">Questions with several options where one is correct</p>
                </div>
                
                <div class="question-type-option" data-type="true_false">
                    <div class="alertTitle text-center">True or False</div>
                    <p class="alertCaption text-center mb-4">Questions with true or false options</p>
                </div>
                
                <div class="question-type-option" data-type="fill_blank">
                    <div class="alertTitle text-center">Fill in the Blank</div>
                    <p class="alertCaption text-center mb-4">Questions with a blank space to fill</p>
                </div>
                
                <div class="question-type-option" data-type="identification">
                    <div class="alertTitle text-center">Identification</div>
                    <p class="alertCaption text-center mb-4">Questions requiring a specific answer</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>