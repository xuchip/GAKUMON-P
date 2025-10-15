<?php
session_start();

$pageTitle = 'GAKUMON — Gakusensei Verification';
$pageCSS = 'CSS/desktop/gakusensei_verificationStyle.css';
$pageJS = 'JS/desktop/gakusensei_verificationScript.js';

// 🚨 Fix 1: Prevent HTML include for POST calls (only include header for GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include 'include/header.php';
}

require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['sUser'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        error_reporting(0);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    } else {
        echo "<script>alert('Unauthorized Access'); window.location.href='login.php';</script>";
        exit;
    }
}

$username = $_SESSION['sUser'];

// Get user role from database
$stmt = $connection->prepare("SELECT user_id, role FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $userID = $row['user_id'];
    $userRole = $row['role'];
} else {
    echo "User not found.";
    exit;
}

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    error_reporting(0); // prevent notices from breaking JSON

    // Check authorization again for API calls
    if ($userRole !== 'Kanri') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $application_id = $_POST['application_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;

    if ($application_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid application ID']);
        exit;
    }

    try {
        if ($action === 'approve') {
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Missing or invalid user ID']);
                exit;
            }

            $connection->begin_transaction();

            // Update application status to approved
            $updateAppStmt = $connection->prepare("UPDATE tbl_creator_applications SET status = 'approved' WHERE application_id = ?");
            $updateAppStmt->bind_param("i", $application_id);

            if (!$updateAppStmt->execute()) {
                throw new Exception("Failed to update application status");
            }

            // Update user role to Gakusensei
            $updateUserStmt = $connection->prepare("UPDATE tbl_user SET role = 'Gakusensei' WHERE user_id = ?");
            $updateUserStmt->bind_param("i", $user_id);

            if (!$updateUserStmt->execute()) {
                throw new Exception("Failed to update user role");
            }

            $connection->commit();

            // Log the Kanri activity
            logAdminAction(
                $connection,
                $userID,                                // Kanri's ID
                'Approved a Gakusensei application',    // Action description
                'user',                                 // Target type
                $user_id                                // Targeted user’s ID
            );

            echo json_encode(['success' => true, 'message' => 'Application approved successfully']);
            exit;

        } elseif ($action === 'reject') {
            // Update application status to rejected
            $stmt = $connection->prepare("UPDATE tbl_creator_applications SET status = 'rejected' WHERE application_id = ?");
            $stmt->bind_param("i", $application_id);

            if ($stmt->execute()) {

                // Log the Kanri activity
                logAdminAction(
                    $connection,
                    $userID,
                    'Rejected a Gakusensei application',
                    'user',
                    $application_id
                );
                echo json_encode(['success' => true, 'message' => 'Application rejected successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject application']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }

    } catch (Exception $e) {
        if ($_POST['action'] === 'approve') {
            $connection->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// 🚨 Fix 2: Only include nav for normal page loads
include 'include/desktopKanriNav.php';
?>


<div class="main-layout">
    <div class="content-area">
        <div class="page-content">
            <div class="card account-card">
                <div class="card-header">
                    <h2>GAKUSENSEI VERIFICATION</h2>
                </div>

                <div class="card-body">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>Application ID</th>
                                <th>Account</th>
                                <th>Educational Attainment</th>
                                <th>School</th>
                                <th>Field of Expertise</th>
                                <th>File Upload</th>
                                <th>Status</th>
                                <th>Submitted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT 
                                        ca.application_id,
                                        ca.educ_attainment,
                                        ca.school,
                                        ca.field_of_expertise,
                                        ca.proof_file_url,
                                        ca.status,
                                        ca.submitted_at,
                                        ca.user_id,
                                        u.username
                                    FROM tbl_creator_applications ca
                                    JOIN tbl_user u ON ca.user_id = u.user_id
                                    ORDER BY ca.submitted_at DESC";
                            
                            $result = $connection->query($query);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $file_name = basename($row['proof_file_url']);
                                    
                                    echo "<tr>
                                        <td>{$row['application_id']}</td>
                                        <td>{$row['username']}</td>
                                        <td>{$row['educ_attainment']}</td>
                                        <td>{$row['school']}</td>
                                        <td>{$row['field_of_expertise']}</td>
                                        <td>
                                            <a href='{$row['proof_file_url']}' target='_blank' class='file-link'>
                                                {$file_name}
                                            </a>
                                        </td>
                                        <td>
                                            <span class='status-badge status-{$row['status']}'>
                                                {$row['status']}
                                            </span>
                                        </td>
                                        <td>" . date('M j, Y g:i A', strtotime($row['submitted_at'])) . "</td>
                                        <td class='action-buttons'>";
                                        
                                    if ($row['status'] == 'pending') {
                                        echo "
                                            <button class='approve-btn' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#approveModal{$row['application_id']}'>
                                                <i class='bi bi-check-lg'></i>
                                            </button>
                                            <button class='reject-btn' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#rejectModal{$row['application_id']}'>
                                                <i class='bi bi-x-lg'></i>
                                            </button>
                                        ";
                                    } else {
                                        echo "
                                            <button class='view-btn' title='View Application'>
                                                <i class='bi bi-eye'></i>
                                            </button>
                                        ";
                                    }
                                    
                                    echo "</td>
                                    </tr>";
                                    
                                    echo "
                                    <!-- Approve Modal for Application {$row['application_id']} -->
                                    <div class='modal fade' id='approveModal{$row['application_id']}' tabindex='-1'>
                                        <div class='modal-dialog modal-dialog-centered'>
                                            <div class='modal-content delete-modal'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Confirm Approval</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <p>Are you sure you want to approve <strong>{$row['username']}'s</strong> application?</p>
                                                    <p class='text-muted'>This will grant them Gakusensei creator privileges.</p>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' class='btn btn-primary' onclick=\"approveApplication({$row['application_id']}, {$row['user_id']})\">Approve Application</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reject Modal for Application {$row['application_id']} -->
                                    <div class='modal fade' id='rejectModal{$row['application_id']}' tabindex='-1'>
                                        <div class='modal-dialog modal-dialog-centered'>
                                            <div class='modal-content delete-modal'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Confirm Rejection</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <p>Are you sure you want to reject <strong>{$row['username']}'s</strong> application?</p>
                                                    <p class='text-muted'>The user will need to submit a new application.</p>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' class='btn btn-danger' onclick=\"rejectApplication({$row['application_id']})\">Reject Application</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr><td colspan='9'>No verification applications found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <!-- Add pagination container -->
                    <div class="pagination-container">
                        <div class="pagination-info"></div>
                        <nav>
                            <ul class="pagination">
                                <!-- Pagination will be generated by JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>