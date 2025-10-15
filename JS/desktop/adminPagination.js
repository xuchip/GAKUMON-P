// Enhanced Pagination Module for Admin Dashboard

// Pagination state tracking
let paginationState = {
    users: { currentPage: 1, totalPages: 1 },
    lessons: { currentPage: 1, totalPages: 1 },
    quizzes: { currentPage: 1, totalPages: 1 }
};

// Enhanced pagination functions with anchoring and state management
function loadUserPage(page) {
    const query = document.getElementById('userSearch')?.value || '';
    const limit = 10;
    
    // Get total count first
    const countParams = new URLSearchParams({
        action: 'get_users_count',
        query: query
    });
    
    fetch(`admin_ajax.php?${countParams}`)
    .then(r => r.json())
    .then(countData => {
        const totalPages = Math.ceil(countData.total / limit);
        paginationState.users.totalPages = totalPages;
        paginationState.users.currentPage = page;
        
        // Load page data
        const params = new URLSearchParams({
            action: 'search_users',
            query: query,
            page: page,
            limit: limit
        });
        
        return fetch(`admin_ajax.php?${params}`);
    })
    .then(r => r.text())
    .then(html => {
        const tableBody = document.getElementById('userTableBody');
        if (tableBody) {
            tableBody.innerHTML = html;
        }
        updatePaginationControls('users', page, paginationState.users.totalPages);
        
        // Anchor to User Management section
        const userSection = document.getElementById('user-management');
        if (userSection) {
            userSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    })
    .catch(err => console.error('User pagination failed:', err));
}

function loadLessonPage(page) {
    const query = document.getElementById('lessonSearch')?.value || '';
    const limit = 10;
    
    // Get total count first
    const countParams = new URLSearchParams({
        action: 'get_lessons_count',
        query: query
    });
    
    fetch(`admin_ajax.php?${countParams}`)
    .then(r => r.json())
    .then(countData => {
        const totalPages = Math.ceil(countData.total / limit);
        paginationState.lessons.totalPages = totalPages;
        paginationState.lessons.currentPage = page;
        
        // Load page data
        const params = new URLSearchParams({
            action: 'search_lessons',
            query: query,
            page: page,
            limit: limit
        });
        
        return fetch(`admin_ajax.php?${params}`);
    })
    .then(r => r.text())
    .then(html => {
        const tableBody = document.getElementById('lessonTableBody');
        if (tableBody) {
            tableBody.innerHTML = html;
        }
        updatePaginationControls('lessons', page, paginationState.lessons.totalPages);
        
        // Anchor to Lesson Management section
        const lessonSection = document.getElementById('lesson-management');
        if (lessonSection) {
            lessonSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    })
    .catch(err => console.error('Lesson pagination failed:', err));
}

function loadQuizPage(page) {
    const query = document.getElementById('quizSearch')?.value || '';
    const limit = 10;
    
    // Get total count first
    const countParams = new URLSearchParams({
        action: 'get_quizzes_count',
        query: query
    });
    
    fetch(`admin_ajax.php?${countParams}`)
    .then(r => r.json())
    .then(countData => {
        const totalPages = Math.ceil(countData.total / limit);
        paginationState.quizzes.totalPages = totalPages;
        paginationState.quizzes.currentPage = page;
        
        // Load page data
        const params = new URLSearchParams({
            action: 'search_quizs',
            query: query,
            page: page,
            limit: limit
        });
        
        return fetch(`admin_ajax.php?${params}`);
    })
    .then(r => r.text())
    .then(html => {
        const tableBody = document.getElementById('quizTableBody');
        if (tableBody) {
            tableBody.innerHTML = html;
        }
        updatePaginationControls('quizzes', page, paginationState.quizzes.totalPages);
        
        // Anchor to Quiz Management section
        const quizSection = document.getElementById('quiz-management');
        if (quizSection) {
            quizSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    })
    .catch(err => console.error('Quiz pagination failed:', err));
}

// Update pagination controls with proper previous/next button states
function updatePaginationControls(type, currentPage, totalPages) {
    const paginationContainer = document.getElementById(`${type}Pagination`);
    if (!paginationContainer) return;
    
    let html = '<div class="pagination-controls">';
    
    // Previous button
    if (currentPage > 1) {
        const funcName = type === 'users' ? 'loadUserPage' : type === 'lessons' ? 'loadLessonPage' : 'loadQuizPage';
        html += `<button class="btn btn-secondary" onclick="${funcName}(${currentPage - 1})">Previous</button>`;
    } else {
        html += '<button class="btn btn-secondary" disabled>Previous</button>';
    }
    
    // Page info
    html += `<span class="page-info">Page ${currentPage} of ${totalPages}</span>`;
    
    // Next button
    if (currentPage < totalPages) {
        const funcName = type === 'users' ? 'loadUserPage' : type === 'lessons' ? 'loadLessonPage' : 'loadQuizPage';
        html += `<button class="btn btn-primary" onclick="${funcName}(${currentPage + 1})">Next</button>`;
    } else {
        html += '<button class="btn btn-primary" disabled>Next</button>';
    }
    
    html += '</div>';
    paginationContainer.innerHTML = html;
}

// Enhanced search function that resets pagination
function performSearch(type, query) {
    // Reset to page 1 for new searches and update pagination
    if (type === 'user') {
        loadUserPage(1);
    } else if (type === 'lesson') {
        loadLessonPage(1);
    } else if (type === 'quiz') {
        loadQuizPage(1);
    }
}

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination for all tables
    setTimeout(() => {
        if (document.getElementById('userTableBody')) {
            loadUserPage(1);
        }
        if (document.getElementById('lessonTableBody')) {
            loadLessonPage(1);
        }
        if (document.getElementById('quizTableBody')) {
            loadQuizPage(1);
        }
    }, 500);
});