<?php
// Shop Management Section
?>
<section id="shop-management" class="management-section">
    <div class="section-header">
        <h2>Shop Management</h2>
        <div class="section-actions">
            <button class="btn btn-primary" onclick="showAddItemModal()">
                <i class="bi bi-plus-circle"></i> Add Item
            </button>
            <button class="btn btn-secondary" onclick="exportTableData('shopTable', 'shop_items.csv')">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Tabs for Shop Management -->
    <div class="tabs">
        <button class="tab-button active" onclick="openShopTab('items')">Shop Items</button>
        <button class="tab-button" onclick="openShopTab('pets')">Pet Management</button>
        <button class="tab-button" onclick="openShopTab('inventory')">User Inventory</button>
    </div>

    <!-- Shop Items Tab -->
    <div id="items-tab" class="tab-content active">
        <div class="table-container">
            <table id="shopTable" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Energy</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items_result = $connection->query("
                        SELECT * FROM tbl_shop_items 
                        ORDER BY item_type, price
                    ");
                    
                    while ($item = $items_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $item['item_id']; ?></td>
                        <td>
                            <span style="font-size: 24px;"><?php echo $item['image_url']; ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $item['item_type'] == 'food' ? 'success' : ($item['item_type'] == 'accessory' ? 'primary' : 'info'); ?>">
                                <?php echo ucfirst($item['item_type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo $item['price']; ?> coins</td>
                        <td><?php echo $item['energy_restore'] ?: 'N/A'; ?></td>
                        <td><?php echo $item['image_url']; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="editShopItem(<?php echo $item['item_id']; ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteShopItem(<?php echo $item['item_id']; ?>)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                                <button class="btn-action btn-primary" onclick="grantItemToUser(<?php echo $item['item_id']; ?>)">
                                    <i class="bi bi-gift"></i> Grant
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pet Management Tab -->
    <div id="pets-tab" class="tab-content">
        <div class="table-container">
            <table id="petsTable" class="data-table">
                <thead>
                    <tr>
                        <th>Pet ID</th>
                        <th>Name</th>
                        <th>Image</th>
                        <th>Users</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pets_result = $connection->query("
                        SELECT p.*, COUNT(up.user_pet_id) as user_count
                        FROM tbl_pet p
                        LEFT JOIN tbl_user_pet up ON p.pet_id = up.pet_id
                        GROUP BY p.pet_id
                    ");
                    
                    while ($pet = $pets_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $pet['pet_id']; ?></td>
                        <td><?php echo htmlspecialchars($pet['pet_name']); ?></td>
                        <td>
                            <?php if ($pet['image_url']): ?>
                                <img src="<?php echo $pet['image_url']; ?>" alt="<?php echo $pet['pet_name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <span class="text-muted">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $pet['user_count']; ?> users</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="editPet(<?php echo $pet['pet_id']; ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn-action btn-view" onclick="viewPetUsers(<?php echo $pet['pet_id']; ?>)">
                                    <i class="bi bi-people"></i> View Users
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Inventory Tab -->
    <div id="inventory-tab" class="tab-content">
        <div class="search-filters">
            <div class="search-box">
                <input type="text" id="inventorySearch" placeholder="Search users..." class="search-input">
                <i class="bi bi-search"></i>
            </div>
        </div>
        
        <div class="table-container">
            <table id="inventoryTable" class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Food Items</th>
                        <th>Accessories</th>
                        <th>Pet</th>
                        <th>Pet Energy</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $inventory_result = $connection->query("
                        SELECT u.user_id, u.username, 
                               COUNT(DISTINCT uf.user_food_id) as food_count,
                               COUNT(DISTINCT ua.user_accessory_id) as accessory_count,
                               up.custom_name, up.energy_level, p.pet_name
                        FROM tbl_user u
                        LEFT JOIN tbl_user_foods uf ON u.user_id = uf.user_id
                        LEFT JOIN tbl_user_accessories ua ON u.user_id = ua.user_id
                        LEFT JOIN tbl_user_pet up ON u.user_id = up.user_id
                        LEFT JOIN tbl_pet p ON up.pet_id = p.pet_id
                        GROUP BY u.user_id
                        ORDER BY u.username
                        LIMIT 50
                    ");
                    
                    while ($user = $inventory_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <br><small>ID: <?php echo $user['user_id']; ?></small>
                        </td>
                        <td><?php echo $user['food_count']; ?> items</td>
                        <td><?php echo $user['accessory_count']; ?> items</td>
                        <td>
                            <?php if ($user['custom_name']): ?>
                                <?php echo htmlspecialchars($user['custom_name']); ?> (<?php echo $user['pet_name']; ?>)
                            <?php else: ?>
                                <span class="text-muted">No pet</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['energy_level'] !== null): ?>
                                <div class="energy-bar">
                                    <div class="energy-fill" style="width: <?php echo $user['energy_level']; ?>%"></div>
                                    <span class="energy-text"><?php echo $user['energy_level']; ?>%</span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-view" onclick="viewUserInventory(<?php echo $user['user_id']; ?>)">
                                    <i class="bi bi-box"></i> Inventory
                                </button>
                                <button class="btn-action btn-primary" onclick="manageUserPet(<?php echo $user['user_id']; ?>)">
                                    <i class="bi bi-heart"></i> Pet
                                </button>
                                <button class="btn-action btn-success" onclick="grantItemsToUser(<?php echo $user['user_id']; ?>)">
                                    <i class="bi bi-gift"></i> Grant Items
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Shop Item Modal -->
    <div id="shopItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="shopItemModalTitle">Add Shop Item</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="shopItemForm">
                    <input type="hidden" id="item_id" name="item_id">
                    <div class="form-group">
                        <label for="item_type">Item Type</label>
                        <select id="item_type" name="item_type" class="form-control" required>
                            <option value="food">Food</option>
                            <option value="accessory">Accessory</option>
                            <option value="wallpaper">Wallpaper</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="item_name">Item Name</label>
                        <input type="text" id="item_name" name="item_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (coins)</label>
                            <input type="number" id="price" name="price" class="form-control" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="energy_restore">Energy Restore (food only)</label>
                            <input type="number" id="energy_restore" name="energy_restore" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="image_url">Image (Emoji or URL)</label>
                        <input type="text" id="image_url" name="image_url" class="form-control" required>
                        <small>Use emoji (🍲) or image URL</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeShopItemModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveShopItem()">Save Item</button>
            </div>
        </div>
    </div>
</section>