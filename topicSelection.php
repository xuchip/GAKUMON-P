<?php
    session_start();

    $pageTitle = 'GAKUMON — Topic Selection';
    $pageCSS = 'CSS/desktop/topicSelectionStyle.css';
    $pageJS = 'JS/desktop/topicSelectionScript.js';

    include 'include/header.php';
    require_once 'config/config.php';    // Database Connection

    // // Check if user is logged in
    // if(!isset($_SESSION['sUser'])) {
    //     header("Location: login.php");
    //     exit;
    // }

    // Get user ID from session
    if (!isset($_SESSION['sUserID'])) {
    header("Location: login.php");
    exit;
    }
    $user_id = $_SESSION['sUserID']; // You'll need to store this during login

    // Errors Array
    $errors = [];

    // Fetch all topics from database
    $topics = [];
    $stmt = $connection->prepare("SELECT topic_id, topic_name, topic_icon FROM tbl_topic ORDER BY topic_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }
    $stmt->close();

    // Process form submission
    if(isset($_POST['save_topics'])) {
        if(isset($_POST['selected_topics']) && !empty($_POST['selected_topics'])) {
            // If it's a string, split it
            if (is_string($_POST['selected_topics'])) {
                $selected_topics = explode(',', $_POST['selected_topics']);
            } else {
                $selected_topics = $_POST['selected_topics'];
            }
            // $selected_topics = $_POST['selected_topics'];
            
            // Prepare statement to insert user topic preferences
            $stmt = $connection->prepare("INSERT INTO tbl_user_fav_topic (user_id, topic_id) VALUES (?, ?)");
            
            foreach($selected_topics as $topic_id) {
                // Validate topic_id exists
                $topic_id = intval($topic_id);
                $stmt->bind_param("ii", $user_id, $topic_id);
                $stmt->execute();
            }
            
            $stmt->close();
            
            // Redirect to petSelection
            header("Location: petSelection.php");
            exit;
        } else {
            $errors[] = "Please select at least one topic.";
        }
    }
?>

<div class="transition-overlay"></div>
<div class="container page-contents">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="logo-container d-flex justify-content-center mb-4">
                <img src="IMG/Logos/logo_text_landscape_red.png" alt="Logo" class="logo img-fluid">
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="card-title text-center">Select Your Interests</div>
                    <p class="caption text-center mb-4">Choose topics you're interested in to personalize your experience</p>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="topicSelection.php" id="topicForm">
                        <div class="form-group mb-4">
                            <label for="topicSearch" class="form-label">Search Topics</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="topicSearch" 
                                    placeholder="Enter a topic">
                                <span class="searchbtn input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <div id="searchSuggestions" class="suggestions-container"></div>
                        </div>

                        <div class="mb-4">
                            <div class="lbl">Suggested Topics</div>
                            <div class="topics-container" id="suggestedTopics">
                                <?php foreach($topics as $topic): ?>
                                    <div class="items topic-item" data-topic-id="<?php echo $topic['topic_id']; ?>">
                                        <span class="topic-name"><?php echo htmlspecialchars($topic['topic_name']); ?></span>
                                        <i class="fas fa-plus add-topic"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-4 selected-topics-container">
                            <div class="lbl">Your Selected Topics</div>
                            <div id="selectedTopics" class="selected-topics">
                                <p class="plainTexttext-muted no-topics">No topics selected yet. Search or select from suggestions above.</p>
                            </div>
                        </div>

                        <input type="hidden" name="selected_topics" id="selectedTopicsInput">

                        <div class="form-group row mt-4">
                            <div class="col-12 submitButton">
                                <button type="submit" name="save_topics" class="btnSubmit btn btn-lg btn-primary w-100" id="letsGoButton" style="display: none;">Let's Go!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>