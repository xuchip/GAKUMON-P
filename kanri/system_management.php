<?php
// System Management Section
?>
<section id="system-management" class="management-section">
    <div class="section-header">
        <h2>System Management</h2>
        <div class="section-actions">
            <button class="btn btn-primary" onclick="showAddTopicModal()">
                <i class="bi bi-plus-circle"></i> Add Topic
            </button>
            <button class="btn btn-secondary" onclick="exportTableData('auditTable', 'audit_logs.csv')">
                <i class="bi bi-download"></i> Export Logs
            </button>
        </div>
    </div>

    <!-- Tabs for System Management -->
    <div class="tabs">
        <button class="tab-button active" onclick="openSystemTab('topics')">Topics</button>
        <button class="tab-button" onclick="openSystemTab('audit')">Audit Logs</button>
        <button class="tab-button" onclick="openSystemTab('feedback')">Feedback</button>
        <button class="tab-button" onclick="openSystemTab('pending')">Pending Verifications</button>
    </div>

    <!-- Topics Tab -->
    <div id="topics-tab" class="tab-content active">
        <div class="table-container">
            <table id="topicsTable" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Topic Name</th>
                        <th>Icon</th>
                        <th>Lessons</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $topics_result = $connection->query("
                        SELECT t.*, COUNT(l.lesson_id) as lesson_count
                        FROM tbl_topic t
                        LEFT JOIN tbl_lesson l ON t.topic_id = l.topic_id
                        GROUP BY t.topic_id
                        ORDER BY t.topic_name
                    ");
                    
                    while ($topic = $topics_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $topic['topic_id']; ?></td>
                        <td><?php echo htmlspecialchars($topic['topic_name']); ?></td>
                        <td>
                            <?php if ($topic['topic_icon']): ?>
                                <div class="topic-icon">
                                    <?php echo $topic['topic_icon']; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No icon</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $topic['lesson_count']; ?> lessons</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="editTopic(<?php echo $topic['topic_id']; ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteTopic(<?php echo $topic['topic_id']; ?>)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Audit Logs Tab -->
    <div id="audit-tab" class="tab-content">
        <div class="search-filters">
            <div class="search-box">
                <input type="text" id="auditSearch" placeholder="Search audit logs..." class="search-input">
                <i class="bi bi-search"></i>
            </div>
            <div class="filters">
                <select id="actionTypeFilter" class="filter-select">
                    <option value="">All Actions</option>
                    <option value="user">User</option>
                    <option value="lesson">Lesson</option>
                    <option value="quiz">Quiz</option>
                    <option value="item">Item</option>
                </select>
                <input type="date" id="auditDateFilter" class="filter-select">
            </div>
        </div>

        <div class="table-container">
            <table id="auditTable" class="data-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Admin User</th>
                        <th>Action</th>
                        <th>Target Type</th>
                        <th>Target ID</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $audit_result = $connection->query("
                        SELECT al.*, u.username as admin_username
                        FROM tbl_admin_audit_logs al
                        JOIN tbl_user u ON al.user_id = u.user_id
                        ORDER BY al.created_at DESC
                        LIMIT 100
                    ");
                    
                    while ($log = $audit_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $log['log_id']; ?></td>
                        <td><?php echo htmlspecialchars($log['admin_username']); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $log['target_type']; ?>">
                                <?php echo ucfirst($log['target_type']); ?>
                            </span>
                        </td>
                        <td><?php echo $log['target_id']; ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Feedback Tab -->
    <div id="feedback-tab" class="tab-content">
        <div class="table-container">
            <table id="feedbackTable" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Feedback</th>
                        <th>Rating</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $feedback_result = $connection->query("
                        SELECT * FROM tbl_feedback 
                        ORDER BY submitted_at DESC
                        LIMIT 50
                    ");
                    
                    while ($feedback = $feedback_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $feedback['feedback_id']; ?></td>
                        <td>
                            <?php if ($feedback['user_id']): ?>
                                User #<?php echo $feedback['user_id']; ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($feedback['user_name']); ?>
                                <br><small><?php echo htmlspecialchars($feedback['user_email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="feedback-text">
                                <?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($feedback['rating']): ?>
                                <div class="rating-stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $feedback['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                    <br><small>(<?php echo $feedback['rating']; ?>/5)</small>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No rating</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($feedback['submitted_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-view" onclick="viewFeedback(<?php echo $feedback['feedback_id']; ?>)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteFeedback(<?php echo $feedback['feedback_id']; ?>)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pending Verifications Tab -->
    <div id="pending-tab" class="tab-content">
        <div class="table-container">
            <table id="pendingTable" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Verification Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pending_result = $connection->query("
                        SELECT * FROM tbl_pending_verif 
                        ORDER BY index_id DESC
                    ");
                    
                    while ($pending = $pending_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $pending['index_id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($pending['username']); ?></td>
                        <td><?php echo htmlspecialchars($pending['email_address']); ?></td>
                        <td><?php echo $pending['verif_code']; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-success" onclick="approveVerification(<?php echo $pending['index_id']; ?>)">
                                    <i class="bi bi-check"></i> Approve
                                </button>
                                <button class="btn-action btn-danger" onclick="rejectVerification(<?php echo $pending['index_id']; ?>)">
                                    <i class="bi bi-x"></i> Reject
                                </button>
                                <button class="btn-action btn-view" onclick="viewPendingDetails(<?php echo $pending['index_id']; ?>)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Topic Modal -->
    <div id="topicModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="topicModalTitle">Add Topic</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="topicForm">
                    <input type="hidden" id="topic_id" name="topic_id">
                    <div class="form-group">
                        <label for="topic_name">Topic Name</label>
                        <input type="text" id="topic_name" name="topic_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="topic_icon">Topic Icon (HTML)</label>
                        <textarea id="topic_icon" name="topic_icon" class="form-control" rows="3" placeholder='<i class="bi bi-icon-name"></i>'></textarea>
                        <small>Use Bootstrap Icons HTML code</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTopicModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTopic()">Save Topic</button>
            </div>
        </div>
    </div>
</section>