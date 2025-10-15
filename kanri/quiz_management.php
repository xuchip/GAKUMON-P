<?php
// Quiz Management Section
?>
<section id="quiz-management" class="management-section">
    <div class="section-header">
        <h2>Quiz Management</h2>
        <div class="section-actions">
            <button class="btn btn-primary" onclick="showAddQuizModal()">
                <i class="bi bi-plus-circle"></i> Add Quiz
            </button>
            <button class="btn btn-secondary" onclick="exportTableData('quizTable', 'quizzes.csv')">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Quiz Search and Filters -->
    <div class="search-filters">
        <div class="search-box">
            <input type="text" id="quizSearch" placeholder="Search quizzes..." class="search-input">
            <i class="bi bi-search"></i>
        </div>
        <div class="filters">
            <select id="lessonFilter" class="filter-select">
                <option value="">All Lessons</option>
                <?php
                $lessons_result = $connection->query("SELECT lesson_id, title FROM tbl_lesson");
                while ($lesson = $lessons_result->fetch_assoc()) {
                    echo "<option value='{$lesson['lesson_id']}'>" . htmlspecialchars($lesson['title']) . "</option>";
                }
                ?>
            </select>
            <select id="aiFilter" class="filter-select">
                <option value="">All Types</option>
                <option value="1">AI Generated</option>
                <option value="0">Manual</option>
            </select>
        </div>
    </div>

    <!-- Quizzes Table -->
    <div class="table-container">
        <table id="quizTable" class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Lesson</th>
                    <th>Questions</th>
                    <th>Type</th>
                    <th>Author</th>
                    <th>Attempts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $quizzes_result = $connection->query("
                    SELECT q.quiz_id, q.title, q.is_ai_generated, q.created_at,
                           l.title as lesson_title, u.username as author_name,
                           COUNT(DISTINCT qn.question_id) as question_count,
                           COUNT(DISTINCT qa.attempt_id) as attempt_count
                    FROM tbl_quizzes q
                    LEFT JOIN tbl_lesson l ON q.lesson_id = l.lesson_id
                    LEFT JOIN tbl_user u ON q.author_id = u.user_id
                    LEFT JOIN tbl_questions qn ON q.quiz_id = qn.quiz_id
                    LEFT JOIN tbl_user_quiz_attempts qa ON q.quiz_id = qa.quiz_id
                    GROUP BY q.quiz_id
                    ORDER BY q.created_at DESC
                ");
                
                while ($quiz = $quizzes_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $quiz['quiz_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($quiz['title'] ?: 'Untitled Quiz'); ?></strong>
                    </td>
                    <td><?php echo $quiz['lesson_title'] ?: 'Standalone Quiz'; ?></td>
                    <td><?php echo $quiz['question_count']; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $quiz['is_ai_generated'] ? 'info' : 'primary'; ?>">
                            <?php echo $quiz['is_ai_generated'] ? 'AI Generated' : 'Manual'; ?>
                        </span>
                    </td>
                    <td><?php echo $quiz['author_name'] ?: 'System'; ?></td>
                    <td><?php echo $quiz['attempt_count']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-edit" onclick="editQuiz(<?php echo $quiz['quiz_id']; ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>)">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            <button class="btn-action btn-view" onclick="viewQuizDetails(<?php echo $quiz['quiz_id']; ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="btn-action btn-secondary" onclick="manageQuestions(<?php echo $quiz['quiz_id']; ?>)">
                                <i class="bi bi-question-circle"></i> Questions
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Quiz Modal -->
    <div id="quizModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="quizModalTitle">Add Quiz</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="quizForm">
                    <input type="hidden" id="quiz_id" name="quiz_id">
                    <div class="form-group">
                        <label for="quiz_title">Quiz Title</label>
                        <input type="text" id="quiz_title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="lesson_id">Associated Lesson (Optional)</label>
                        <select id="lesson_id" name="lesson_id" class="form-control">
                            <option value="">Standalone Quiz</option>
                            <?php
                            $lessons_result = $connection->query("SELECT lesson_id, title FROM tbl_lesson");
                            while ($lesson = $lessons_result->fetch_assoc()) {
                                echo "<option value='{$lesson['lesson_id']}'>" . htmlspecialchars($lesson['title']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_ai_generated" name="is_ai_generated" value="1">
                            <span class="checkmark"></span>
                            AI Generated Content
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="author_id">Author (Optional)</label>
                        <select id="author_id" name="author_id" class="form-control">
                            <option value="">System (No Author)</option>
                            <?php
                            $authors_result = $connection->query("
                                SELECT user_id, username, first_name, last_name 
                                FROM tbl_user 
                                WHERE role IN ('Gakusensei', 'Kanri')
                            ");
                            while ($author = $authors_result->fetch_assoc()) {
                                $name = $author['first_name'] . ' ' . $author['last_name'] . ' (' . $author['username'] . ')';
                                echo "<option value='{$author['user_id']}'>" . htmlspecialchars($name) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeQuizModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveQuiz()">Save Quiz</button>
            </div>
        </div>
    </div>
</section>