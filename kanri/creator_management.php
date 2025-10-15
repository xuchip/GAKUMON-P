<?php
// Creator Management Section
?>
<section id="creator-management" class="management-section">
    <div class="section-header">
        <h2>Creator Management</h2>
        <div class="section-actions">
            <button class="btn btn-secondary" onclick="exportTableData('creatorTable', 'creators.csv')">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Tabs for Creator Management -->
    <div class="tabs">
        <button class="tab-button active" onclick="openCreatorTab('applications')">Pending Applications</button>
        <button class="tab-button" onclick="openCreatorTab('earnings')">Creator Earnings</button>
        <button class="tab-button" onclick="openCreatorTab('payouts')">Payout Management</button>
    </div>

    <!-- Applications Tab -->
    <div id="applications-tab" class="tab-content active">
        <div class="table-container">
            <table id="applicationsTable" class="data-table">
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>User</th>
                        <th>Education</th>
                        <th>School</th>
                        <th>Expertise</th>
                        <th>Proof File</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $applications_result = $connection->query("
                        SELECT ca.*, u.username, u.email_address 
                        FROM tbl_creator_applications ca
                        JOIN tbl_user u ON ca.user_id = u.user_id
                        ORDER BY ca.submitted_at DESC
                    ");
                    
                    while ($app = $applications_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $app['application_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($app['username']); ?></strong>
                            <br><small><?php echo htmlspecialchars($app['email_address']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($app['educ_attainment']); ?></td>
                        <td><?php echo htmlspecialchars($app['school']); ?></td>
                        <td><?php echo htmlspecialchars($app['field_of_expertise']); ?></td>
                        <td>
                            <?php if ($app['proof_file_url']): ?>
                                <a href="<?php echo $app['proof_file_url']; ?>" target="_blank" class="file-link">
                                    <i class="bi bi-file-earmark"></i> View Proof
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No file</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($app['submitted_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($app['status'] == 'pending'): ?>
                                    <button class="btn-action btn-success" onclick="approveApplication(<?php echo $app['application_id']; ?>)">
                                        <i class="bi bi-check"></i> Approve
                                    </button>
                                    <button class="btn-action btn-danger" onclick="rejectApplication(<?php echo $app['application_id']; ?>)">
                                        <i class="bi bi-x"></i> Reject
                                    </button>
                                <?php endif; ?>
                                <button class="btn-action btn-view" onclick="viewApplication(<?php echo $app['application_id']; ?>)">
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

    <!-- Earnings Tab -->
    <div id="earnings-tab" class="tab-content">
        <div class="table-container">
            <table id="earningsTable" class="data-table">
                <thead>
                    <tr>
                        <th>Earning ID</th>
                        <th>Creator</th>
                        <th>Lesson</th>
                        <th>Metric Type</th>
                        <th>Metric Value</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $earnings_result = $connection->query("
                        SELECT ce.*, u.username, l.title as lesson_title
                        FROM tbl_creator_earnings ce
                        JOIN tbl_user u ON ce.user_id = u.user_id
                        LEFT JOIN tbl_lesson l ON ce.lesson_id = l.lesson_id
                        ORDER BY ce.recorded_at DESC
                        LIMIT 50
                    ");
                    
                    while ($earning = $earnings_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $earning['earning_id']; ?></td>
                        <td><?php echo htmlspecialchars($earning['username']); ?></td>
                        <td><?php echo htmlspecialchars($earning['lesson_title'] ?: 'N/A'); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $earning['metric_type'])); ?></td>
                        <td><?php echo $earning['metric_value']; ?></td>
                        <td>₱<?php echo number_format($earning['earned_amount'], 2); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($earning['recorded_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payouts Tab -->
    <div id="payouts-tab" class="tab-content">
        <div class="table-container">
            <table id="payoutsTable" class="data-table">
                <thead>
                    <tr>
                        <th>Payout ID</th>
                        <th>Creator</th>
                        <th>Bank</th>
                        <th>Account Number</th>
                        <th>Total Earnings</th>
                        <th>Last Payout</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $payouts_result = $connection->query("
                        SELECT cp.*, u.username, gb.bank_name, gb.account_number
                        FROM tbl_creator_payouts cp
                        JOIN tbl_user u ON cp.user_id = u.user_id
                        LEFT JOIN tbl_gakusensei_bank_info gb ON u.user_id = gb.user_id
                        ORDER BY cp.last_payout DESC
                    ");
                    
                    while ($payout = $payouts_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $payout['payout_id']; ?></td>
                        <td><?php echo htmlspecialchars($payout['username']); ?></td>
                        <td><?php echo htmlspecialchars($payout['bank_name']); ?></td>
                        <td><?php echo htmlspecialchars($payout['account_number']); ?></td>
                        <td>₱<?php echo number_format($payout['total_earnings'], 2); ?></td>
                        <td><?php echo $payout['last_payout'] ? date('M j, Y', strtotime($payout['last_payout'])) : 'Never'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-primary" onclick="processPayout(<?php echo $payout['payout_id']; ?>)">
                                    <i class="bi bi-cash"></i> Process Payout
                                </button>
                                <button class="btn-action btn-view" onclick="viewPayoutDetails(<?php echo $payout['payout_id']; ?>)">
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
</section>