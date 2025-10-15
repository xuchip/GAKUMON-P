<?php
// User Management Section
?>
<section id="user-management" class="management-section">
    <div class="section-header">
        <h2>User Management</h2>
        <div class="section-actions">
            <button class="btn btn-primary" onclick="showAddUserModal()">
                <i class="bi bi-plus-circle"></i> Add User
            </button>
            <button class="btn btn-secondary" onclick="exportTableData('userTable', 'users.csv')">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- User Search and Filters -->
    <div class="search-filters">
        <div class="search-box">
            <input type="text" id="userSearch" placeholder="Search users..." class="search-input">
            <i class="bi bi-search"></i>
        </div>
        <div class="filters">
            <select id="roleFilter" class="filter-select">
                <option value="">All Roles</option>
                <option value="Gakusei">Gakusei</option>
                <option value="Gakusensei">Gakusensei</option>
                <option value="Kanri">Kanri</option>
            </select>
            <select id="subscriptionFilter" class="filter-select">
                <option value="">All Subscriptions</option>
                <option value="Free">Free</option>
                <option value="Premium">Premium</option>
            </select>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table id="userTable" class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Subscription</th>
                    <th>Gakucoins</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users_result = $connection->query("
                    SELECT user_id, username, email_address, first_name, last_name, role, 
                           subscription_type, gakucoins, is_verified, created_at 
                    FROM tbl_user 
                    ORDER BY created_at DESC
                ");
                
                while ($user = $users_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email_address']); ?></td>
                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($user['role']); ?>">
                            <?php echo $user['role']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($user['subscription_type']); ?>">
                            <?php echo $user['subscription_type']; ?>
                        </span>
                    </td>
                    <td><?php echo $user['gakucoins']; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                            <?php echo $user['is_verified'] ? 'Verified' : 'Pending'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            <button class="btn-action btn-view" onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Add User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="user_id" name="user_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email_address">Email Address</label>
                        <input type="email" id="email_address" name="email_address" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control">
                        <small>Leave blank to keep current password</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="Gakusei">Gakusei</option>
                                <option value="Gakusensei">Gakusensei</option>
                                <option value="Kanri">Kanri</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subscription_type">Subscription Type</label>
                            <select id="subscription_type" name="subscription_type" class="form-control" required>
                                <option value="Free">Free</option>
                                <option value="Premium">Premium</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="gakucoins">Gakucoins</label>
                        <input type="number" id="gakucoins" name="gakucoins" class="form-control" required min="0">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_verified" name="is_verified" value="1">
                            <span class="checkmark"></span>
                            User Verified
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Save User</button>
            </div>
        </div>
    </div>
</section>