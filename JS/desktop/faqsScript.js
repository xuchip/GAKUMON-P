// ===== NAVIGATION DROPDOWN FUNCTIONALITY =====
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

// ===== FAQ PAGE FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function() {
    // Category tab switching
    const categoryTabs = document.querySelectorAll('.category-tab');
    const faqCategories = document.querySelectorAll('.faq-category');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Update active tab
            categoryTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding FAQ category
            faqCategories.forEach(cat => {
                cat.classList.remove('active');
                if (cat.id === `${category}-faqs`) {
                    cat.classList.add('active');
                }
            });
        });
    });
    
    // Initialize first accordion items to be expanded
    initializeAccordions();
});

// Function to initialize accordions with first item expanded in each category
function initializeAccordions() {
    // Expand first item in General category (active by default)
    const generalFirstButton = document.querySelector('#generalAccordion .accordion-button');
    if (generalFirstButton && !generalFirstButton.classList.contains('collapsed')) {
        const generalTarget = generalFirstButton.getAttribute('data-bs-target');
        const generalCollapse = document.querySelector(generalTarget);
        if (generalCollapse) {
            generalCollapse.classList.add('show');
        }
    }
    
    // For other categories, we'll handle when they become active
    setupAccordionListeners();
}

// Setup accordion listeners for when categories become active
function setupAccordionListeners() {
    const categoryTabs = document.querySelectorAll('.category-tab');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Small delay to ensure DOM is updated before manipulating accordions
            setTimeout(() => {
                expandFirstAccordionInCategory(category);
            }, 50);
        });
    });
}

// Expand first accordion item in the specified category
function expandFirstAccordionInCategory(category) {
    const activeCategory = document.getElementById(`${category}-faqs`);
    if (!activeCategory) return;
    
    // Close all accordions in this category first
    const allButtons = activeCategory.querySelectorAll('.accordion-button');
    const allCollapses = activeCategory.querySelectorAll('.accordion-collapse');
    
    allButtons.forEach(button => {
        button.classList.add('collapsed');
    });
    
    allCollapses.forEach(collapse => {
        collapse.classList.remove('show');
    });
    
    // Expand first accordion
    const firstButton = activeCategory.querySelector('.accordion-button');
    const firstCollapse = activeCategory.querySelector('.accordion-collapse');
    
    if (firstButton && firstCollapse) {
        firstButton.classList.remove('collapsed');
        firstCollapse.classList.add('show');
    }
}