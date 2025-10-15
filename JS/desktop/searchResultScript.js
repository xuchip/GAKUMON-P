// Toggle dropdown visibility
document.getElementById('accountDropdownBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('accountDropdown');
    dropdown.classList.toggle('show');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('accountDropdown');
    const accountBtn = document.getElementById('accountDropdownBtn');
    if (!dropdown.contains(e.target) && e.target !== accountBtn && !accountBtn.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Enable horizontal scrolling with mouse wheel for tabs
document.addEventListener('DOMContentLoaded', function() {
    const tabsScroll = document.querySelector('.tabs-scroll');
    
    // Enable horizontal scrolling with mouse wheel
    if (tabsScroll) {
        tabsScroll.addEventListener('wheel', function(e) {
            if (e.deltaY !== 0) {
                // Prevent vertical scrolling only if we're scrolling horizontally
                if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                    e.preventDefault();
                }
                // Scroll horizontally
                this.scrollLeft += e.deltaY;
            }
        });
    }
    
    // Initialize the page
    init();
    
    // Make sure the cards container doesn't create a scrollbar
    const cardsContainer = document.querySelector('.cards-container');
    if (cardsContainer) {
        cardsContainer.style.overflowY = 'hidden';
    }

    // Search functionality
    const searchForm = document.getElementById('lessonSearchForm');
    const searchInput = document.getElementById('lessonSearchInput');

    if (searchForm && searchInput) {
        // Search and filter lessons even when entering or clicking the search button WITHOUT redirecting
        searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = searchInput.value.trim().toLowerCase();
        searchLessons(query); // Filter and show lessons without redirect
        });

        // Search function with redirection (CAN REMOVE IF NOT NEEDED ^_^)
        // searchForm.addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     const query = searchInput.value.trim();
        //     if (query) {
        //         // Redirect to search results page with query parameter
        //         window.location.href = `searchResults.php?query=${encodeURIComponent(query)}`;
        //     }
        // });

        // Optional
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim().toLowerCase();
            searchLessons(query);
        });
    }
});

// Search lessons and display results
function searchLessons(query) {
    let filteredLessons;
    if (!query) {
        // If search is empty, show all lessons in current category
        showLessons(currentCategory);
        return;
    }
    // Search in all lessons (title + description)
    filteredLessons = lessons.all.filter(lesson =>
        lesson.title.toLowerCase().includes(query) ||
        (lesson.short_desc && lesson.short_desc.toLowerCase().includes(query)) ||
        (lesson.topic && lesson.topic.toLowerCase().includes(query))
    );

    // Show filtered lessons
    showFilteredLessons(filteredLessons);
}

// Show filtered lessons (no pagination for search results)
function showFilteredLessons(filteredLessons) {
    cardsGrid.innerHTML = '';
    if (filteredLessons.length === 0) {
        cardsGrid.innerHTML = '<div class="no-results">No lessons found.</div>';
        return;
    }
    filteredLessons.forEach(lesson => {
        const card = document.createElement('div');
        card.className = 'lesson-card';
        card.innerHTML = `
            <div class="card-img">
                ${lesson.icon}
            </div>
            <div class="card-content">
                <div class="lesson-title">${lesson.title}</div>
                <div class="labels">
                    <div class="label label-gaku">GakuLesson</div>
                    <div class="label label-topic">${lesson.topic}</div>
                </div>
                <div class="lesson-description">${lesson.short_desc}</div>
                <div class="card-meta">
                    <span>${lesson.duration}</span>
                    <span>${lesson.difficulty}</span>
                </div>
            </div>
        `;
        card.addEventListener('click', () => {
            openLessonModal(lesson);
        });
        cardsGrid.appendChild(card);
    });
}

// Lessons Card

// Sample lesson data
// const lessons = {
//     all: [
//         { id: 1, title: "Keyboards", description: "Learn about different types of keyboards and their functions", duration: "45 min", level: "Beginner", icon: "fa-keyboard", topic: "Input Devices" },
//         { id: 2, title: "HTML & CSS Basics", description: "Introduction to web page structure and styling", duration: "60 min", level: "Beginner", icon: "fa-code", topic: "Web Programming" },
//         { id: 3, title: "JavaScript Variables", description: "Understanding variables and data types in JavaScript", duration: "50 min", level: "Beginner", icon: "fa-file-code", topic: "JavaScript" },
//         { id: 4, title: "Computer Components", description: "Basic parts of a computer system", duration: "40 min", level: "Beginner", icon: "fa-laptop", topic: "Intro to Computing" },
//         { id: 5, title: "Early Computing Devices", description: "From abacus to analytical engine", duration: "55 min", level: "Intermediate", icon: "fa-history", topic: "Computer History" },
//         { id: 6, title: "SQL Basics", description: "Introduction to database querying", duration: "65 min", level: "Intermediate", icon: "fa-database", topic: "Databases" },
//         { id: 7, title: "Network Topologies", description: "Understanding different network layouts", duration: "50 min", level: "Intermediate", icon: "fa-network-wired", topic: "Networking" },
//         { id: 8, title: "Mice & Pointing Devices", description: "Types and functions of computer pointing devices", duration: "35 min", level: "Beginner", icon: "fa-mouse", topic: "Input Devices" },
//         { id: 9, title: "JavaScript Functions", description: "Creating and using functions in JavaScript", duration: "70 min", level: "Intermediate", icon: "fa-file-code", topic: "JavaScript" }
//     ],
//     input: [
//         { id: 1, title: "Keyboards", description: "Learn about different types of keyboards and their functions", duration: "45 min", level: "Beginner", icon: "fa-keyboard", topic: "Input Devices" },
//         { id: 8, title: "Mice & Pointing Devices", description: "Types and functions of computer pointing devices", duration: "35 min", level: "Beginner", icon: "fa-mouse", topic: "Input Devices" },
//         { id: 10, title: "Touchscreens", description: "How touch input works and different technologies", duration: "40 min", level: "Intermediate", icon: "fa-mobile-alt", topic: "Input Devices" },
//         { id: 11, title: "Scanners", description: "Types of scanners and their applications", duration: "30 min", level: "Intermediate", icon: "fa-scanner", topic: "Input Devices" }
//     ],
//     web: [
//         { id: 2, title: "HTML & CSS Basics", description: "Introduction to web page structure and styling", duration: "60 min", level: "Beginner", icon: "fa-code", topic: "Web Programming" },
//         { id: 12, title: "Responsive Design", description: "Creating websites that work on all devices", duration: "75 min", level: "Intermediate", icon: "fa-desktop", topic: "Web Programming" },
//         { id: 13, title: "CSS Frameworks", description: "Using Bootstrap and other CSS frameworks", duration: "65 min", level: "Intermediate", icon: "fa-paint-brush", topic: "Web Programming" }
//     ],
//     js: [
//         { id: 3, title: "JavaScript Variables", description: "Understanding variables and data types in JavaScript", duration: "50 min", level: "Beginner", icon: "fa-file-code", topic: "JavaScript" },
//         { id: 9, title: "JavaScript Functions", description: "Creating and using functions in JavaScript", duration: "70 min", level: "Intermediate", icon: "fa-file-code", topic: "JavaScript" },
//         { id: 14, title: "DOM Manipulation", description: "Working with the Document Object Model", duration: "80 min", level: "Intermediate", icon: "fa-file-code", topic: "JavaScript" }
//     ],
//     intro: [
//         { id: 4, title: "Computer Components", description: "Basic parts of a computer system", duration: "40 min", level: "Beginner", icon: "fa-laptop", topic: "Intro to Computing" },
//         { id: 15, title: "Operating Systems", description: "Overview of different OS and their functions", duration: "55 min", level: "Beginner", icon: "fa-window-restore", topic: "Intro to Computing" },
//         { id: 16, title: "File Management", description: "Organizing and managing files and folders", duration: "45 min", level: "Beginner", icon: "fa-folder-open", topic: "Intro to Computing" }
//     ],
//     history: [
//         { id: 5, title: "Early Computing Devices", description: "From abacus to analytical engine", duration: "55 min", level: "Intermediate", icon: "fa-history", topic: "History of Computers" },
//         { id: 17, title: "Personal Computer Revolution", description: "The rise of home computing in the 80s", duration: "60 min", level: "Intermediate", icon: "fa-history", topic: "History of Computers" },
//         { id: 18, title: "Internet History", description: "From ARPANET to the modern internet", duration: "70 min", level: "Intermediate", icon: "fa-globe", topic: "History of Computers" }
//     ],
//     database: [
//         { id: 6, title: "SQL Basics", description: "Introduction to database querying", duration: "65 min", level: "Intermediate", icon: "fa-database", topic: "Databases" },
//         { id: 19, title: "Database Design", description: "Principles of good database structure", duration: "75 min", level: "Advanced", icon: "fa-database", topic: "Databases" },
//         { id: 20, title: "Normalization", description: "Organizing data to reduce redundancy", duration: "70 min", level: "Advanced", icon: "fa-database", topic: "Databases" }
//     ],
//     network: [
//         { id: 7, title: "Network Topologies", description: "Understanding different network layouts", duration: "50 min", level: "Intermediate", icon: "fa-network-wired", topic: "Networking" },
//         { id: 21, title: "TCP/IP Protocol", description: "Fundamentals of internet communication", duration: "65 min", level: "Advanced", icon: "fa-wifi", topic: "Networking" },
//         { id: 22, title: "Network Security", description: "Protecting networks from threats", duration: "80 min", level: "Advanced", icon: "fa-shield-alt", topic: "Networking" }
//     ]
// };

// DOM elements
const tabs = document.querySelectorAll('.tab');
const cardsGrid = document.querySelector('.cards-grid');
const paginationLinks = document.querySelectorAll('.page-link');

// Current state
let currentCategory = 'all';
let currentPage = 1;
const itemsPerPage = 9;

// Initialize the page
function init() {
    // Add event listeners to tabs
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const category = tab.getAttribute('data-category');
            setActiveTab(tab);
            showLessons(category);
        });
    });
    
    // Add event listeners to pagination
    paginationLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const action = link.classList.contains('prev') ? 'prev' : 
                            link.classList.contains('next') ? 'next' : 'page';
            
            if (action === 'prev' && currentPage > 1) {
                currentPage--;
                showLessons(currentCategory);
            } else if (action === 'next' && currentPage < getTotalPages()) {
                currentPage++;
                showLessons(currentCategory);
            } else if (action === 'page') {
                currentPage = parseInt(link.textContent);
                showLessons(currentCategory);
            }
            
            updatePagination();
        });
    });
    
    // Load initial lessons
    showLessons(currentCategory);
}

// Set active tab
function setActiveTab(activeTab) {
    tabs.forEach(tab => tab.classList.remove('active'));
    activeTab.classList.add('active');
    currentCategory = activeTab.getAttribute('data-category');
    currentPage = 1; // Reset to first page when changing category
}

// Show lessons for the selected category
function showLessons(category) {
    const categoryLessons = lessons[category] || [];
    const startIndex = (currentPage - 1) * itemsPerPage;
    const paginatedLessons = categoryLessons.slice(startIndex, startIndex + itemsPerPage);
    
    // Clear the grid
    cardsGrid.innerHTML = '';
    
    // Add lessons to the grid
    paginatedLessons.forEach(lesson => {
        const card = document.createElement('div');
        card.className = 'lesson-card';
        card.innerHTML = `
            <div class="card-img">

            </div>
            <div class="card-content">
                <div class="lesson-title">${lesson.title}</div>
                <div class="labels">
                    <div class="label label-gaku">GakuLesson</div>
                    <div class="label label-topic">${lesson.topic}</div>
                </div>
                <div class="lesson-description">${lesson.short_desc}</div>
                <div class="card-meta">
                    <span>${lesson.duration}</span>
                    <span>${lesson.difficulty}</span>
                </div>
            </div>
        `;
        cardsGrid.appendChild(card);
    });
    
    // Update pagination
    updatePagination();
}

// Get total pages for current category
function getTotalPages() {
    return Math.ceil((lessons[currentCategory] || []).length / itemsPerPage);
}

// Update pagination UI
function updatePagination() {
    const totalPages = getTotalPages();
    const pageItems = document.querySelectorAll('.page-item');
    
    // Hide all page number items first
    pageItems.forEach(item => {
        if (!item.querySelector('.prev') && !item.querySelector('.next')) {
            item.style.display = 'none';
        }
    });
    
    // Show only necessary pages
    let startPage = Math.max(1, currentPage - 1);
    let endPage = Math.min(totalPages, startPage + 2);
    
    if (endPage - startPage < 2) {
        startPage = Math.max(1, endPage - 2);
    }
    
    let pageIndex = 0;
    for (let i = startPage; i <= endPage; i++) {
        if (pageItems[pageIndex + 1]) {
            pageItems[pageIndex + 1].style.display = 'block';
            const pageLink = pageItems[pageIndex + 1].querySelector('.page-link');
            pageLink.textContent = i;
            pageLink.classList.toggle('active', i === currentPage);
            pageIndex++;
        }
    }
    
    // Update prev/next buttons state
    document.querySelector('.prev').parentElement.style.display = 'block';
    document.querySelector('.next').parentElement.style.display = 'block';
    
    document.querySelector('.prev').style.opacity = currentPage > 1 ? '1' : '0.5';
    document.querySelector('.next').style.opacity = currentPage < totalPages ? '1' : '0.5';
}

// Add these modal functions to your existing JavaScript

// Function to open modal with lesson details
function openLessonModal(lesson) {
    const modal = document.getElementById('lessonModal');
    const modalBody = document.querySelector('.modal-lesson-content');
    
    // Populate modal content
    modalBody.innerHTML = `
        <div class="modal-lesson-header">
            <div class="lesson-title">${lesson.title}</div>
            <div class="labels">
                <div class="label label-gaku">GakuLesson</div>
                <div class="label label-topic">${lesson.topic}</div>
            </div>
        
            <div class="modal-meta">
                <span><i class="fas fa-clock"></i> ${lesson.duration}</span>
                <span><i class="fas fa-signal"></i> ${lesson.difficulty}</span>
            </div>
        </div>
        <div class="modal-lesson-description">
            <div class="lesson-description">${lesson.short_desc}</div>
        </div>
        <div class="modal-lesson-objectives">
            <h4>Learning Objectives</h4>
            <ul>
                <li>Understand the core concepts of ${lesson.title}</li>
                <li>Apply knowledge in practical scenarios</li>
                <li>Complete exercises to reinforce learning</li>
            </ul>
        </div>
        <div class="modal-lesson-progress">
            <h4>Your Progress</h4>
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress" style="width: 0%"></div>
                </div>
                <span class="progress-text">0% Complete</span>
            </div>
        </div>
    `;
    
    // Show the modal with animation
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Add event listeners to close buttons
    const closeButtons = document.querySelectorAll('.custom-modal-close, .custom-modal-close-btn');
    closeButtons.forEach(button => {
        button.addEventListener('click', closeLessonModal);
    });
    
    // Add event listener to the Start Lesson button
    const startLessonBtn = document.querySelector('.start-lesson-btn');
    startLessonBtn.onclick = function() {
        // Redirect to lesson page (you'll need to implement this)
        window.location.href = `lesson.php?id=${lesson.id}`;
    };
    
    // Close modal when clicking on backdrop
    const backdrop = document.querySelector('.custom-modal-backdrop');
    backdrop.addEventListener('click', closeLessonModal);
}

// Function to close the modal
function closeLessonModal() {
    const modal = document.getElementById('lessonModal');
    modal.classList.remove('active');
    document.body.style.overflow = ''; // Re-enable scrolling
    
    // Remove event listeners to prevent memory leaks
    const closeButtons = document.querySelectorAll('.custom-modal-close, .custom-modal-close-btn');
    closeButtons.forEach(button => {
        button.removeEventListener('click', closeLessonModal);
    });
    
    const backdrop = document.querySelector('.custom-modal-backdrop');
    backdrop.removeEventListener('click', closeLessonModal);
}

// Update the showLessons function to add click event to cards
function showLessons(category) {
    // const categoryLessons = lessons[category] || [];
    let categoryLessons;
    if (category === 'all') {
        categoryLessons = lessons.all;
    } else {
        categoryLessons = lessons.all.filter(lesson => lesson.topic.toLowerCase().replace(/\s/g, '') === category);
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const paginatedLessons = categoryLessons.slice(startIndex, startIndex + itemsPerPage);
    
    // Clear the grid
    cardsGrid.innerHTML = '';
    
    // Add lessons to the grid
    paginatedLessons.forEach(lesson => {
        const card = document.createElement('div');
        card.className = 'lesson-card';
        card.innerHTML = `
            <div class="card-img">
                ${lesson.icon}
            </div>
            <div class="card-content">
                <div class="lesson-title">${lesson.title}</div>
                <div class="labels">
                    <div class="label label-gaku">GakuLesson</div>
                    <div class="label label-topic">${lesson.topic}</div>
                </div>
                <div class="lesson-description">${lesson.short_desc}</div>
                <div class="card-meta">
                    <span>${lesson.duration}</span>
                    <span>${lesson.difficulty}</span>
                </div>
            </div>
        `;
        
        // Add click event to open modal
        card.addEventListener('click', () => {
            openLessonModal(lesson);
        });
        
        cardsGrid.appendChild(card);
    });
    
    // Update pagination
    updatePagination();
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLessonModal();
    }
});