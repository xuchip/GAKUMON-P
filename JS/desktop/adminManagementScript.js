// Management Sections JavaScript

// Tab Management
function openCreatorTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('#creator-management .tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('#creator-management .tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show the selected tab and activate the button
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

function openShopTab(tabName) {
    document.querySelectorAll('#shop-management .tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('#shop-management .tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

function openSystemTab(tabName) {
    document.querySelectorAll('#system-management .tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('#system-management .tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

// Modal Management
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Close modal with close button
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
        this.closest('.modal').style.display = 'none';
    });
});

// User Management Functions
function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    openModal('userModal');
}

function closeUserModal() {
    closeModal('userModal');
}

function editUser(userId) {
    fetch(`admin_ajax.php?action=get_user&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userModalTitle').textContent = 'Edit User';
                document.getElementById('user_id').value = data.user.user_id;
                document.getElementById('first_name').value = data.user.first_name;
                document.getElementById('last_name').value = data.user.last_name;
                document.getElementById('username').value = data.user.username;
                document.getElementById('email_address').value = data.user.email_address;
                document.getElementById('role').value = data.user.role;
                document.getElementById('subscription_type').value = data.user.subscription_type;
                document.getElementById('gakucoins').value = data.user.gakucoins;
                document.getElementById('is_verified').checked = data.user.is_verified;
                openModal('userModal');
            } else {
                showNotification('Error loading user data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading user data', 'error');
        });
}

function saveUser() {
    const formData = new FormData(document.getElementById('userForm'));
    const userId = document.getElementById('user_id').value;
    const url = userId ? 'admin_ajax.php?action=update_user' : 'admin_ajax.php?action=create_user';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeUserModal();
            showNotification('User saved successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error saving user: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving user', 'error');
    });
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_user&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error deleting user: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting user', 'error');
        });
    }
}

function viewUserDetails(userId) {
    window.open(`user_profile.php?user_id=${userId}`, '_blank');
}

// Lesson Management Functions
function showAddLessonModal() {
    document.getElementById('lessonModalTitle').textContent = 'Add Lesson';
    document.getElementById('lessonForm').reset();
    document.getElementById('lesson_id').value = '';
    openModal('lessonModal');
}

function closeLessonModal() {
    closeModal('lessonModal');
}

function editLesson(lessonId) {
    fetch(`admin_ajax.php?action=get_lesson&lesson_id=${lessonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('lessonModalTitle').textContent = 'Edit Lesson';
                document.getElementById('lesson_id').value = data.lesson.lesson_id;
                document.getElementById('lesson_title').value = data.lesson.title;
                document.getElementById('short_desc').value = data.lesson.short_desc;
                document.getElementById('long_desc').value = data.lesson.long_desc;
                document.getElementById('topic_id').value = data.lesson.topic_id;
                document.getElementById('difficulty_level').value = data.lesson.difficulty_level;
                document.getElementById('duration').value = data.lesson.duration;
                document.getElementById('is_private').checked = data.lesson.is_private;
                document.getElementById('author_id').value = data.lesson.author_id || '';
                openModal('lessonModal');
            } else {
                showNotification('Error loading lesson data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading lesson data', 'error');
        });
}

function saveLesson() {
    const formData = new FormData(document.getElementById('lessonForm'));
    const lessonId = document.getElementById('lesson_id').value;
    const url = lessonId ? 'admin_ajax.php?action=update_lesson' : 'admin_ajax.php?action=create_lesson';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeLessonModal();
            showNotification('Lesson saved successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error saving lesson: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving lesson', 'error');
    });
}

function deleteLesson(lessonId) {
    if (confirm('Are you sure you want to delete this lesson? All associated files and quizzes will also be deleted.')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_lesson&lesson_id=${lessonId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Lesson deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error deleting lesson: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting lesson', 'error');
        });
    }
}

function viewLessonDetails(lessonId) {
    window.open(`lesson_view.php?lesson_id=${lessonId}`, '_blank');
}

// Quiz Management Functions
function showAddQuizModal() {
    document.getElementById('quizModalTitle').textContent = 'Add Quiz';
    document.getElementById('quizForm').reset();
    document.getElementById('quiz_id').value = '';
    openModal('quizModal');
}

function closeQuizModal() {
    closeModal('quizModal');
}

function editQuiz(quizId) {
    fetch(`admin_ajax.php?action=get_quiz&quiz_id=${quizId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('quizModalTitle').textContent = 'Edit Quiz';
                document.getElementById('quiz_id').value = data.quiz.quiz_id;
                document.getElementById('quiz_title').value = data.quiz.title || '';
                document.getElementById('lesson_id').value = data.quiz.lesson_id || '';
                document.getElementById('is_ai_generated').checked = data.quiz.is_ai_generated;
                document.getElementById('author_id').value = data.quiz.author_id || '';
                openModal('quizModal');
            } else {
                showNotification('Error loading quiz data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading quiz data', 'error');
        });
}

function saveQuiz() {
    const formData = new FormData(document.getElementById('quizForm'));
    const quizId = document.getElementById('quiz_id').value;
    const url = quizId ? 'admin_ajax.php?action=update_quiz' : 'admin_ajax.php?action=create_quiz';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeQuizModal();
            showNotification('Quiz saved successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error saving quiz: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving quiz', 'error');
    });
}

function deleteQuiz(quizId) {
    if (confirm('Are you sure you want to delete this quiz? All associated questions and attempts will also be deleted.')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_quiz&quiz_id=${quizId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Quiz deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error deleting quiz: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting quiz', 'error');
        });
    }
}

function viewQuizDetails(quizId) {
    window.open(`quiz_view.php?quiz_id=${quizId}`, '_blank');
}

function manageQuestions(quizId) {
    window.open(`quiz_questions.php?quiz_id=${quizId}`, '_blank');
}

// Creator Management Functions
function approveApplication(applicationId) {
    if (confirm('Are you sure you want to approve this creator application?')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve_application&application_id=${applicationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Application approved successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error approving application: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error approving application', 'error');
        });
    }
}

function rejectApplication(applicationId) {
    if (confirm('Are you sure you want to reject this creator application?')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject_application&application_id=${applicationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Application rejected successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error rejecting application: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error rejecting application', 'error');
        });
    }
}

function viewApplication(applicationId) {
    // Open application details in modal or new page
    console.log('View application:', applicationId);
}

function processPayout(payoutId) {
    if (confirm('Are you sure you want to process this payout?')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=process_payout&payout_id=${payoutId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Payout processed successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error processing payout: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error processing payout', 'error');
        });
    }
}

function viewPayoutDetails(payoutId) {
    console.log('View payout details:', payoutId);
}

// Shop Management Functions
function showAddItemModal() {
    document.getElementById('shopItemModalTitle').textContent = 'Add Shop Item';
    document.getElementById('shopItemForm').reset();
    document.getElementById('item_id').value = '';
    openModal('shopItemModal');
}

function closeShopItemModal() {
    closeModal('shopItemModal');
}

function editShopItem(itemId) {
    fetch(`admin_ajax.php?action=get_shop_item&item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('shopItemModalTitle').textContent = 'Edit Shop Item';
                document.getElementById('item_id').value = data.item.item_id;
                document.getElementById('item_type').value = data.item.item_type;
                document.getElementById('item_name').value = data.item.item_name;
                document.getElementById('description').value = data.item.description;
                document.getElementById('price').value = data.item.price;
                document.getElementById('energy_restore').value = data.item.energy_restore || '';
                document.getElementById('image_url').value = data.item.image_url;
                openModal('shopItemModal');
            } else {
                showNotification('Error loading item data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading item data', 'error');
        });
}

function saveShopItem() {
    const formData = new FormData(document.getElementById('shopItemForm'));
    const itemId = document.getElementById('item_id').value;
    const url = itemId ? 'admin_ajax.php?action=update_shop_item' : 'admin_ajax.php?action=create_shop_item';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeShopItemModal();
            showNotification('Shop item saved successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error saving shop item: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving shop item', 'error');
    });
}

function deleteShopItem(itemId) {
    if (confirm('Are you sure you want to delete this shop item? This will remove it from the shop but existing user purchases will remain.')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_shop_item&item_id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Shop item deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error deleting shop item: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting shop item', 'error');
        });
    }
}

function grantItemToUser(itemId) {
    const userId = prompt('Enter the User ID to grant this item to:');
    if (userId) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=grant_item&item_id=${itemId}&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Item granted successfully', 'success');
            } else {
                showNotification('Error granting item: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error granting item', 'error');
        });
    }
}

function editPet(petId) {
    console.log('Edit pet:', petId);
}

function viewPetUsers(petId) {
    console.log('View pet users:', petId);
}

function viewUserInventory(userId) {
    console.log('View user inventory:', userId);
}

function manageUserPet(userId) {
    console.log('Manage user pet:', userId);
}

function grantItemsToUser(userId) {
    console.log('Grant items to user:', userId);
}

// System Management Functions
function showAddTopicModal() {
    document.getElementById('topicModalTitle').textContent = 'Add Topic';
    document.getElementById('topicForm').reset();
    document.getElementById('topic_id').value = '';
    openModal('topicModal');
}

function closeTopicModal() {
    closeModal('topicModal');
}

function editTopic(topicId) {
    fetch(`admin_ajax.php?action=get_topic&topic_id=${topicId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('topicModalTitle').textContent = 'Edit Topic';
                document.getElementById('topic_id').value = data.topic.topic_id;
                document.getElementById('topic_name').value = data.topic.topic_name;
                document.getElementById('topic_icon').value = data.topic.topic_icon || '';
                openModal('topicModal');
            } else {
                showNotification('Error loading topic data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading topic data', 'error');
        });
}

function saveTopic() {
    const formData = new FormData(document.getElementById('topicForm'));
    const topicId = document.getElementById('topic_id').value;
    const url = topicId ? 'admin_ajax.php?action=update_topic' : 'admin_ajax.php?action=create_topic';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeTopicModal();
            showNotification('Topic saved successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error saving topic: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving topic', 'error');
    });
}

function deleteTopic(topicId) {
    if (confirm('Are you sure you want to delete this topic? This will affect all lessons associated with this topic.')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_topic&topic_id=${topicId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Topic deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error deleting topic: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting topic', 'error');
        });
    }
}

function viewFeedback(feedbackId) {
    console.log('View feedback:', feedbackId);
}

function deleteFeedback(feedbackId) {
    if (confirm('Are you sure you want to delete this feedback?')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_feedback&feedback_id=${feedbackId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Feedback deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error deleting feedback: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting feedback', 'error');
        });
    }
}

function approveVerification(indexId) {
    if (confirm('Are you sure you want to approve this user verification?')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve_verification&index_id=${indexId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Verification approved successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error approving verification: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error approving verification', 'error');
        });
    }
}

function rejectVerification(indexId) {
    if (confirm('Are you sure you want to reject this user verification?')) {
        fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject_verification&index_id=${indexId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Verification rejected successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error rejecting verification: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error rejecting verification', 'error');
        });
    }
}

function viewPendingDetails(indexId) {
    console.log('View pending details:', indexId);
}

// Search and Filter Functions
function setupSearchFilters() {
    // User search
    document.getElementById('userSearch')?.addEventListener('input', debounce(function() {
        filterTable('userTable', this.value);
    }, 300));

    // Lesson search
    document.getElementById('lessonSearch')?.addEventListener('input', debounce(function() {
        filterTable('lessonTable', this.value);
    }, 300));

    // Quiz search
    document.getElementById('quizSearch')?.addEventListener('input', debounce(function() {
        filterTable('quizTable', this.value);
    }, 300));

    // Inventory search
    document.getElementById('inventorySearch')?.addEventListener('input', debounce(function() {
        filterTable('inventoryTable', this.value);
    }, 300));

    // Audit search
    document.getElementById('auditSearch')?.addEventListener('input', debounce(function() {
        filterTable('auditTable', this.value);
    }, 300));
}

function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchText.toLowerCase();

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchLower) ? '' : 'none';
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export Function
function exportTableData(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Remove action buttons from export
            if (!cols[j].querySelector('.action-buttons')) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV file
    const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupSearchFilters();
    
    // Set up section navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            
            // Update active nav item
            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
            });
            this.classList.add('active');
            
            // Scroll to section
            if (section !== 'dashboard') {
                document.getElementById(section).scrollIntoView({
                    behavior: 'smooth'
                });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    });
});