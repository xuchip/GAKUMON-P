<?php
   session_start();

   $pageTitle = 'GAKUMON — Gakusensei Payout';
   $pageCSS = 'CSS/desktop/gakusenseiPayoutStyle.css';
   $pageJS = 'JS/desktop/gakusenseiPayoutScript.js';

   include 'include/header.php';
   require_once 'config/config.php'; // Database Connection

   if (isset($_SESSION['sUser'])) {
      $username = $_SESSION['sUser'];

      // Get UserID from database
      $stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($row = $result->fetch_assoc()) {
         $userID = $row['user_id'];   // Now you have the userID
      } else {
         echo "User not found.";
         exit;
      }

   } else {
      echo "User not logged in.";
      header("Location: login.php");
      exit;
   }

   // Session and role validation
    if (!isset($_SESSION['sUser']) || $_SESSION['sRole'] !== 'Kanri') {
        header("Location: login.php");
        exit;
    }

   include 'include/desktopKanriNav.php';
?>

<!-- Main layout with three columns -->
<div class="main-layout">
   <!-- Left navigation (already fixed by your CSS) -->
    <div class="content-area">
        <div class="page-content">
            <div class="card account-card">
                <div class="card-header">
                    <h2>GAKUSENSEI PAYOUT APPROVAL</h2>
                    <button class="btn btn-success" id="approveAllBtn">
                        <i class="bi bi-check-all"></i> Approve All Payouts
                    </button>
                </div>

                <div class="card-body">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>Gakusensei ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Total Earnings</th>
                                <th>Bank Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch Gakusensei with earnings above 0
                            $query = "SELECT 
                                        u.user_id, 
                                        u.first_name, 
                                        u.last_name, 
                                        u.username, 
                                        u.email_address,
                                        cp.bank_name,
                                        cp.account_number,
                                        cp.qr_code_url,
                                        COALESCE(SUM(ce.earned_amount), 0) as total_earnings,
                                        COALESCE(SUM(ce.total_earnings), 0) as lifetime_earnings
                                    FROM tbl_user u
                                    LEFT JOIN tbl_creator_payouts cp ON u.user_id = cp.user_id
                                    LEFT JOIN tbl_creator_earnings ce ON u.user_id = ce.user_id
                                    WHERE u.role = 'Gakusensei'
                                    GROUP BY u.user_id, u.first_name, u.last_name, u.username, u.email_address, cp.bank_name, cp.account_number, cp.qr_code_url
                                    HAVING total_earnings > 0
                                    ORDER BY total_earnings DESC";
                            
                            $result = $connection->query($query);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$row['user_id']}</td>
                                        <td>{$row['first_name']}</td>
                                        <td>{$row['last_name']}</td>
                                        <td>{$row['username']}</td>
                                        <td>{$row['email_address']}</td>
                                        <td>₱" . number_format($row['total_earnings'], 2) . "</td>
                                        <td>
                                            <strong>Bank:</strong> " . ($row['bank_name'] ?? 'Not set') . "<br>
                                            <strong>Account:</strong> " . ($row['account_number'] ?? 'Not set') . "
                                        </td>
                                        <td class='action-buttons'>
                                            <button class='approve-btn' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#approveModal{$row['user_id']}'>
                                                <i class='bi bi-check-lg'></i> Approve
                                            </button>
                                        </td>
                                    </tr>";
                                    
                                    // Echo the approve modal for the specific Gakusensei
                                    echo "
                                    <!-- Approve Modal for Gakusensei {$row['user_id']} -->
                                    <div class='modal fade' id='approveModal{$row['user_id']}' tabindex='-1'>
                                        <div class='modal-dialog modal-dialog-centered'>
                                            <div class='modal-content approve-modal'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Confirm Payout Approval</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <p>Are you sure you want to approve the payout for <strong>{$row['first_name']} {$row['last_name']}</strong>?</p>
                                                    <div class='payout-details'>
                                                        <p><strong>Total Amount:</strong> ₱" . number_format($row['total_earnings'], 2) . "</p>
                                                        <p><strong>Bank:</strong> " . ($row['bank_name'] ?? 'Not specified') . "</p>
                                                        <p><strong>Account Number:</strong> " . ($row['account_number'] ?? 'Not specified') . "</p>
                                                        " . ($row['qr_code_url'] ? "<p><strong>QR Code:</strong> <img src='{$row['qr_code_url']}' alt='QR Code' style='max-width: 100px;'></p>" : "") . "
                                                    </div>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' class='btn btn-success' onclick='approvePayout({$row['user_id']})'>Approve Payout</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No pending payouts found</td></tr>";
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