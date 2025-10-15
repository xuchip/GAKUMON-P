// Function to save pet item edits
function savePetEdit(itemId) {
    const form = document.getElementById(`editPetForm${itemId}`);
    const formData = new FormData(form);
    
    // Show loading state
    const saveBtn = document.querySelector(`#editPetModal${itemId} .btn-primary`);
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    saveBtn.disabled = true;

    // Add action parameter
    formData.append('action', 'edit_item');

    fetch('update_pet_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Item updated successfully!', 'success');
            
            // Close the modal and reload immediately
            const modalElement = document.getElementById(`editPetModal${itemId}`);
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            
            // Reload the page right away to see changes
            window.location.reload();
            
        } else {
            throw new Error(data.message || 'Failed to update item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating item: ' + error.message, 'error');
        
        // Reset button state on error
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Function to confirm and delete pet item
function confirmDeletePet(itemId) {
    const deleteBtn = document.querySelector(`#deletePetModal${itemId} .btn-danger`);
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
    deleteBtn.disabled = true;

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('action', 'delete_item');

    fetch('delete_pet_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Item deleted successfully!', 'success');
            
            // Close the modal and reload immediately
            const modalElement = document.getElementById(`deletePetModal${itemId}`);
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            
            // Reload the page right away to see changes
            window.location.reload();
            
        } else {
            throw new Error(data.message || 'Failed to delete item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting item: ' + error.message, 'error');
        
        // Reset button state on error
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

// Function to add new pet item
function addNewItem() {
    const form = document.getElementById('addPetForm');
    const formData = new FormData(form);
    
    // Show loading state
    const addBtn = document.querySelector('#addPetModal .btn-primary');
    const originalText = addBtn.innerHTML;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
    addBtn.disabled = true;

    // Add action parameter
    formData.append('action', 'add_item');

    fetch('pet_customization.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Item added successfully!', 'success');
            
            // Close the modal and reload immediately
            const modalElement = document.getElementById('addPetModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            
            // Reload the page right away to see the new item
            window.location.reload();
            
        } else {
            throw new Error(data.message || 'Failed to add item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding item: ' + error.message, 'error');
        
        // Reset button state on error
        addBtn.innerHTML = originalText;
        addBtn.disabled = false;
    });
}

// Keep the notification function the same
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `custom-notification alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Account dropdown functionality
    const accountBtn = document.querySelector('.account-btn');
    const accountDropdown = document.querySelector('.account-dropdown');

    if (accountBtn && accountDropdown) {
        accountBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            accountDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            accountDropdown.classList.remove('show');
        });

        // Prevent dropdown from closing when clicking inside
        accountDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

// Extra safeguard – remove leftover backdrops after load
window.addEventListener('load', () => {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
});