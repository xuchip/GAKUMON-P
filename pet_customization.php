<?php
session_start();

$pageTitle = 'GAKUMON — Pet Customization Management';
$pageCSS = 'CSS/desktop/pet_customizationStyle.css';
$pageJS = 'JS/desktop/pet_customizationScript.js';

// ===== HANDLE FORM SUBMISSIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    handleFormSubmission();
    exit; // Stop execution after handling the form
}

function handleFormSubmission() {
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['sUser'])) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit;
    }
    
    require_once 'config/config.php'; // Database Connection
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit_item') {
        editItem($connection);
    } elseif ($action === 'delete_item') {
        deleteItem($connection);
    } elseif ($action === 'add_item') {
        addItem($connection);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
    $connection->close();
}

function editItem($connection) {
    $item_id = $_POST['item_id'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $hp_value = $_POST['hp_value'] ?? 0;
    $category = $_POST['category'] ?? '';
    
    // Validate required fields
    if (empty($item_id) || empty($item_name) || empty($image_url) || empty($description) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate price
    if (!is_numeric($price) || $price < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid price']);
        return;
    }
    
    // Validate HP value
    if (!is_numeric($hp_value) || $hp_value < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid HP value']);
        return;
    }
    
    try {
        $stmt = $connection->prepare("UPDATE tbl_shop_items 
                                     SET item_name = ?, 
                                         image_url = ?, 
                                         description = ?, 
                                         price = ?, 
                                         energy_restore = ?, 
                                         item_type = ? 
                                     WHERE item_id = ?");
        
        $stmt->bind_param("sssiisi", $item_name, $image_url, $description, $price, $hp_value, $category, $item_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
        } else {
            throw new Exception('Failed to update item in database');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteItem($connection) {
    $item_id = $_POST['item_id'] ?? '';
    
    if (empty($item_id)) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        return;
    }
    
    try {
        // First, check if the item exists
        $check_stmt = $connection->prepare("SELECT item_id FROM tbl_shop_items WHERE item_id = ?");
        $check_stmt->bind_param("i", $item_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            return;
        }
        
        // Delete the item
        $delete_stmt = $connection->prepare("DELETE FROM tbl_shop_items WHERE item_id = ?");
        $delete_stmt->bind_param("i", $item_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
        } else {
            throw new Exception('Failed to delete item from database');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addItem($connection) {
    $item_name = $_POST['item_name'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $hp_value = $_POST['hp_value'] ?? 0;
    $category = $_POST['category'] ?? '';
    
    // Validate required fields
    if (empty($item_name) || empty($image_url) || empty($description) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate price
    if (!is_numeric($price) || $price < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid price']);
        return;
    }
    
    // Validate HP value
    if (!is_numeric($hp_value) || $hp_value < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid HP value']);
        return;
    }
    
    try {
        $stmt = $connection->prepare("INSERT INTO tbl_shop_items 
                                     (item_name, image_url, description, price, energy_restore, item_type) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssiis", $item_name, $image_url, $description, $price, $hp_value, $category);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item added successfully']);
        } else {
            throw new Exception('Failed to add item to database');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ===== CONTINUE WITH NORMAL PAGE DISPLAY =====
require_once 'config/config.php'; // Database Connection for normal page display
include 'include/header.php';

if (isset($_SESSION['sUser'])) {
    $username = $_SESSION['sUser'];

    // Get UserID from database
    $stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userID = $row['user_id'];
    } else {
        echo "User not found.";
        exit;
    }
} else {
    echo "User not logged in.";
    header("Location: login.php");
    exit;
}

// Pagination setup
$items_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$offset = ($current_page - 1) * $items_per_page;

// Get total count of shop items
$count_query = "SELECT COUNT(*) as total FROM tbl_shop_items";
$count_result = $connection->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Ensure current page doesn't exceed total pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $items_per_page;
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
                    <h2>PET CUSTOMIZATION MANAGEMENT</h2>
                </div>

                <div class="card-body">
                    <!-- Pagination Info -->
                    <!-- <div class="pagination-info">
                        <p class="text-muted">
                            Showing <?php echo min($items_per_page, $total_items - $offset); ?> of <?php echo $total_items; ?> items
                            <?php if ($total_pages > 1): ?>
                                - Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                            <?php endif; ?>
                        </p>
                    </div> -->

                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>Item ID</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Description</th>
                                <th>Coin Price</th>
                                <th>HP Value</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch shop items from tbl_shop_items with pagination
                            $query = "SELECT 
                                        item_id,
                                        item_name,
                                        image_url,
                                        description,
                                        price,
                                        energy_restore as hp_value,
                                        item_type as category
                                    FROM tbl_shop_items
                                    ORDER BY item_type, item_id ASC
                                    LIMIT ? OFFSET ?";
                            
                            $stmt = $connection->prepare($query);
                            $stmt->bind_param("ii", $items_per_page, $offset);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$row['item_id']}</td>
                                        <td><strong>{$row['item_name']}</strong></td>
                                        <td>
                                            <div style='font-size: 24px; text-align: center;'>
                                                {$row['image_url']}
                                            </div>
                                        </td>
                                        <td>{$row['description']}</td>
                                        <td>{$row['price']} coins</td>
                                        <td>" . ($row['hp_value'] ? $row['hp_value'] . ' HP' : 'N/A') . "</td>
                                        <td>
                                            <span class='category-badge category-{$row['category']}'>
                                                {$row['category']}
                                            </span>
                                        </td>
                                        <td class='action-buttons'>
                                            <button class='edit-btn' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#editPetModal{$row['item_id']}'
                                                    title='Edit Item'>
                                                <i class='bi bi-pencil'></i>
                                            </button>
                                            <button class='delete-btn' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#deletePetModal{$row['item_id']}'
                                                    title='Delete Item'>
                                                <i class='bi bi-trash'></i>
                                            </button>
                                        </td>
                                    </tr>";
                                    
                                    // Echo the modals for the specific item
                                    echo "
                                    <!-- Delete Modal for Item {$row['item_id']} -->
                                    <div class='modal fade' id='deletePetModal{$row['item_id']}' tabindex='-1'>
                                        <div class='modal-dialog modal-dialog-centered'>
                                            <div class='modal-content delete-modal'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Confirm Deletion</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <p>Are you sure you want to delete <strong>{$row['item_name']}</strong>? This action cannot be undone.</p>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' class='btn btn-danger' onclick='confirmDeletePet({$row['item_id']})'>Delete Item</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Modal for Item {$row['item_id']} -->
                                    <div class='modal fade' id='editPetModal{$row['item_id']}' tabindex='-1'>
                                        <div class='modal-dialog modal-dialog-centered'>
                                            <div class='modal-content edit-modal'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Edit {$row['item_name']}</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <form id='editPetForm{$row['item_id']}'>
                                                        <input type='hidden' name='item_id' value='{$row['item_id']}'>
                                                        <div class='mb-3'>
                                                            <label class='form-label'>Item Name</label>
                                                            <input type='text' class='form-control' name='item_name' value='{$row['item_name']}' required>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label class='form-label'>Image (Emoji/URL)</label>
                                                            <input type='text' class='form-control' name='image_url' value='{$row['image_url']}' required>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label class='form-label'>Description</label>
                                                            <textarea class='form-control' name='description' rows='3' required>{$row['description']}</textarea>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label class='form-label'>Price (Coins)</label>
                                                            <input type='number' class='form-control' name='price' value='{$row['price']}' required min='0'>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label class='form-label'>HP Value (Food items only)</label>
                                                            <input type='number' class='form-control' name='hp_value' value='{$row['hp_value']}' min='0' placeholder='Leave 0 for non-food items'>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label class='form-label'>Category</label>
                                                            <select class='form-control' name='category' required>
                                                                <option value='food' " . ($row['category'] == 'food' ? 'selected' : '') . ">Food</option>
                                                                <option value='accessory' " . ($row['category'] == 'accessory' ? 'selected' : '') . ">Accessory</option>
                                                                <option value='wallpaper' " . ($row['category'] == 'wallpaper' ? 'selected' : '') . ">Wallpaper</option>
                                                            </select>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' class='btn btn-primary' onclick='savePetEdit({$row['item_id']})'>Save Changes</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>No pet customization items found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- Previous Page -->
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            // Show first page if not in range
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; 
                            
                            // Show last page if not in range
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <!-- Next Page -->
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                    <!-- Add New Item Button -->
                    <div class="text-end mt-4">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPetModal">
                            <i class="bi bi-plus-circle me-2"></i>Add New Item
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add New Item Modal -->
<div class="modal fade" id="addPetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content edit-modal">
            <div class="modal-header">
                <h5 class="modal-title">Add New Pet Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPetForm">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image (Emoji/URL)</label>
                        <input type="text" class="form-control" name="image_url" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (Coins)</label>
                        <input type="number" class="form-control" name="price" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HP Value (Food items only)</label>
                        <input type="number" class="form-control" name="hp_value" min="0" placeholder="Leave 0 for non-food items">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category" required>
                            <option value="food">Food</option>
                            <option value="accessory">Accessory</option>
                            <option value="wallpaper">Wallpaper</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addNewItem()">Add Item</button>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>