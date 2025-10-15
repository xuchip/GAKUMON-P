<?php
// Lesson Management Section
?>
<section id="lesson-management" class="management-section">
    <div class="section-header">
        <h2>Lesson Management</h2>
        <div class="section-actions">
            <button class="btn btn-primary" onclick="showAddLessonModal()">
                <i class="bi bi-plus-circle"></i> Add Lesson
            </button>
            <button class="btn btn-secondary" onclick="exportTableData('lessonTable', 'lessons.csv')">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Lesson Search and Filters -->
    <div class="search-filters">
        <div class="search-box">
            <input type="text" id="lessonSearch" placeholder="Search lessons..." class="search-input">
            <i class="bi bi-search"></i>
        </div>
        <div class="filters">
            <select id="topicFilter" class="filter-select">
                <option value="">All Topics</option>
                <?php
                $topics_result = $connection->query("SELECT topic_id, topic_name FROM tbl_topic");
                while ($topic = $topics_result->fetch_assoc()) {
                    echo "<option value='{$topic['topic_id']}'>{$topic['topic_name']}</option>";
                }
                ?>
            </select>
            <select id="difficultyFilter" class="filter-select">
                <option value="">All Difficulties</option>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Professional">Professional</option>
            </select>
        </div>
    </div>

    <!-- Lessons Table -->
    <div class="table-container">
        <table id="lessonTable" class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Topic</th>
                    <th>Difficulty</th>
                    <th>Duration</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Enrollments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $lessons_result = $connection->query("
                    SELECT l.lesson_id, l.title, l.short_desc, l.difficulty_level, l.duration, 
                           l.is_private, l.created_at, t.topic_name, u.username as author_name,
                           COUNT(e.user_id) as enrollment_count
                    FROM tbl_lesson l
                    LEFT JOIN tbl_topic t ON l.topic_id = t.topic_id
                    LEFT JOIN tbl_user u ON l.author_id = u.user_id
                    LEFT JOIN tbl_user_enrollments e ON l.lesson_id = e.lesson_id
                    GROUP BY l.lesson_id
                    ORDER BY l.created_at DESC
                ");
                
                while ($lesson = $lessons_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $lesson['lesson_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($lesson['title']); ?></strong>
                        <br><small><?php echo htmlspecialchars($lesson['short_desc']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($lesson['topic_name']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($lesson['difficulty_level']); ?>">
                            <?php echo $lesson['difficulty_level']; ?>
                        </span>
                    </td>
                    <td><?php echo $lesson['duration']; ?></td>
                    <td><?php echo $lesson['author_name'] ?: 'System'; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $lesson['is_private'] ? 'warning' : 'success'; ?>">
                            <?php echo $lesson['is_private'] ? 'Private' : 'Public'; ?>
                        </span>
                    </td>
                    <td><?php echo $lesson['enrollment_count']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-edit" onclick="editLesson(<?php echo $lesson['lesson_id']; ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteLesson(<?php echo $lesson['lesson_id']; ?>)">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            <button class="btn-action btn-view" onclick="viewLessonDetails(<?php echo $lesson['lesson_id']; ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Lesson Modal -->
    <div id="lessonModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3 id="lessonModalTitle">Add Lesson</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="lessonForm">
                    <input type="hidden" id="lesson_id" name="lesson_id">
                    <div class="form-group">
                        <label for="lesson_title">Title</label>
                        <input type="text" id="lesson_title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="short_desc">Short Description</label>
                        <textarea id="short_desc" name="short_desc" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="long_desc">Long Description</label>
                        <textarea id="long_desc" name="long_desc" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="topic_id">Topic</label>
                            <select id="topic_id" name="topic_id" class="form-control" required>
                                <option value="">Select Topic</option>
                                <?php
                                $topics_result = $connection->query("SELECT topic_id, topic_name FROM tbl_topic");
                                while ($topic = $topics_result->fetch_assoc()) {
                                    echo "<option value='{$topic['topic_id']}'>{$topic['topic_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="difficulty_level">Difficulty Level</label>
                            <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Professional">Professional</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration">Duration (HH:MM:SS)</label>
                            <input type="text" id="duration" name="duration" class="form-control" required placeholder="00:15:00">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is_private" name="is_private" value="1">
                                <span class="checkmark"></span>
                                Private Lesson
                            </label>
                        </div>
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
                <button type="button" class="btn btn-secondary" onclick="closeLessonModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveLesson()">Save Lesson</button>
            </div>
        </div>
    </div>
</section>