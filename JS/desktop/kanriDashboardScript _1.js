// Mobile device detection and redirection
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function redirectToIndex() {
    window.location.href = 'index.php';
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Show mobile warning if on mobile device
    if (isMobileDevice()) {
        document.querySelector('.mobile-warning-modal').style.display = 'flex';
    }
    
    // Initialize charts
    initializeCharts();
    
    // Initialize management sections
    initializeManagementSections();
    
    // Setup event listeners
    setupEventListeners();
});

// Chart initialization
function initializeCharts() {
    // Destroy existing charts if they exist
    if (window.userGrowthChart) {
        window.userGrowthChart.destroy();
    }
    if (window.topLessonsChart) {
        window.topLessonsChart.destroy();
    }
    
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    window.userGrowthChart = new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: [], // Will be populated from PHP data
            datasets: [{
                label: 'New Users',
                data: [],
                borderColor: '#811212',
                backgroundColor: 'rgba(129, 18, 18, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Top Lessons Chart
    const topLessonsCtx = document.getElementById('topLessonsChart').getContext('2d');
    window.topLessonsChart = new Chart(topLessonsCtx, {
        type: 'bar',
        data: {
            labels: [], // Will be populated from PHP data
            datasets: [{
                label: 'Enrollments',
                data: [],
                backgroundColor: '#4299e1',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Load chart data via AJAX
    loadChartData(window.userGrowthChart, window.topLessonsChart);
}

// Load chart data from server
function loadChartData(userGrowthChart, topLessonsChart) {
    fetch('admin_ajax.php?action=get_chart_data')
        .then(response => response.json())
        .then(data => {
            // Update User Growth Chart
            userGrowthChart.data.labels = data.user_growth.labels;
            userGrowthChart.data.datasets[0].data = data.user_growth.data;
            userGrowthChart.update();
            
            // Update Top Lessons Chart
            topLessonsChart.data.labels = data.top_lessons.labels;
            topLessonsChart.data.datasets[0].data = data.top_lessons.data;
            topLessonsChart.update();
        })
        .catch(error => console.error('Error loading chart data:', error));
}

// Management sections functionality
function initializeManagementSections() {
    // Load initial data for each management section
    loadUserManagementData();
    loadLessonManagementData();
    loadQuizManagementData();
}

function setupEventListeners() {
    // Analytics period filter
    document.getElementById('analyticsPeriod').addEventListener('change', function() {
        updateAnalyticsData(this.value);
    });
    
    // Search functionality
    document.querySelectorAll('.search-input').forEach(input => {
        input.addEventListener('input', function() {
            filterTable(this);
        });
    });
}

function updateAnalyticsData(period) {
    // Show loading state
    document.querySelectorAll('.chart-content').forEach(chart => {
        chart.innerHTML = '<div class="loading">Loading data...</div>';
    });
    
    // Reload charts with new period
    setTimeout(() => {
        initializeCharts();
    }, 500);
}

// Table filtering
function filterTable(input) {
    const filter = input.value.toLowerCase();
    const table = input.closest('.management-section').querySelector('table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// User Management Functions
function loadUserManagementData() {
    fetch('admin_ajax.php?action=get_users')
        .then(response => response.json())
        .then(data => {
            updateUserTable(data);
        })
        .catch(error => console.error('Error loading user data:', error));
}

function updateUserTable(users) {
    const tbody = document.querySelector('#userTable tbody');
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.user_id}</td>
            <td>${user.username}</td>
            <td>${user.email_address}</td>
            <td>${user.role}</td>
            <td>${user.subscription_type}</td>
            <td>${user.gakucoins}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editUser(${user.user_id})">Edit</button>
                <button class="btn-action btn-delete" onclick="deleteUser(${user.user_id})">Delete</button>
            </td>
        </tr>
    `).join('');
}





// Lesson Management Functions
function loadLessonManagementData() {
    fetch('admin_ajax.php?action=get_lessons')
        .then(response => response.json())
        .then(data => {
            updateLessonTable(data);
        })
        .catch(error => console.error('Error loading lesson data:', error));
}

function updateLessonTable(lessons) {
    const tbody = document.querySelector('#lessonTable tbody');
    tbody.innerHTML = lessons.map(lesson => `
        <tr>
            <td>${lesson.lesson_id}</td>
            <td>${lesson.title}</td>
            <td>${lesson.topic_name}</td>
            <td>${lesson.difficulty_level}</td>
            <td>${lesson.is_private ? 'Private' : 'Public'}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editLesson(${lesson.lesson_id})">Edit</button>
                <button class="btn-action btn-delete" onclick="deleteLesson(${lesson.lesson_id})">Delete</button>
            </td>
        </tr>
    `).join('');
}

function editLesson(lessonId) {
    console.log('Edit lesson:', lessonId);
    // Implementation for lesson edit modal
}

function deleteLesson(lessonId) {
    if (confirm('Are you sure you want to delete this lesson?')) {
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
                loadLessonManagementData();
                showNotification('Lesson deleted successfully', 'success');
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

// Notification system
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

// Export functionality
function exportTableData(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
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