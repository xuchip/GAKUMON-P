// Gakusensei Payout Script with Pagination
document.addEventListener('DOMContentLoaded', function() {
    // Pagination variables
    const itemsPerPage = 15;
    let currentPage = 1;
    let allGakusensei = [];

    // Initialize the page
    initPage();

    function initPage() {
        // Get all Gakusensei rows from the table
        const tableBody = document.querySelector('.account-table tbody');
        allGakusensei = Array.from(tableBody.querySelectorAll('tr')).filter(row => 
            !row.querySelector('td')?.textContent?.includes('No pending payouts')
        );

        // Initialize pagination
        setupPagination();
        
        // Show first page
        showPage(1);

        // Add event listener for approve all button
        const approveAllBtn = document.getElementById('approveAllBtn');
        if (approveAllBtn) {
            approveAllBtn.addEventListener('click', function() {
                approveAllPayouts();
            });
        }
    }

    function setupPagination() {
        const totalPages = Math.ceil(allGakusensei.length / itemsPerPage);
        
        // Remove existing pagination if any
        const existingPagination = document.querySelector('.pagination-container');
        if (existingPagination) {
            existingPagination.remove();
        }

        // Create pagination container
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-container';
        
        // Create pagination info
        const paginationInfo = document.createElement('div');
        paginationInfo.className = 'pagination-info';
        paginationContainer.appendChild(paginationInfo);

        // Create pagination navigation
        const paginationNav = document.createElement('nav');
        const paginationList = document.createElement('ul');
        paginationList.className = 'pagination';
        paginationNav.appendChild(paginationList);
        paginationContainer.appendChild(paginationNav);

        // Insert pagination after the table
        const cardBody = document.querySelector('.card-body');
        cardBody.appendChild(paginationContainer);

        // Update pagination display
        updatePagination(totalPages, paginationInfo, paginationList);
    }

    function updatePagination(totalPages, paginationInfo, paginationList) {
        // Clear existing pagination
        paginationList.innerHTML = '';

        // Update pagination info
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, allGakusensei.length);
        paginationInfo.textContent = `Showing ${startItem}-${endItem} of ${allGakusensei.length} Gakusensei`;

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
            <a class="page-link" href="#" aria-label="Previous" ${currentPage === 1 ? 'tabindex="-1"' : ''}>
                <span aria-hidden="true">&laquo;</span>
            </a>
        `;
        prevLi.querySelector('.page-link').addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                showPage(currentPage - 1);
            }
        });
        paginationList.appendChild(prevLi);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            
            pageLi.querySelector('.page-link').addEventListener('click', (e) => {
                e.preventDefault();
                showPage(i);
            });
            
            paginationList.appendChild(pageLi);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `
            <a class="page-link" href="#" aria-label="Next" ${currentPage === totalPages ? 'tabindex="-1"' : ''}>
                <span aria-hidden="true">&raquo;</span>
            </a>
        `;
        nextLi.querySelector('.page-link').addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                showPage(currentPage + 1);
            }
        });
        paginationList.appendChild(nextLi);
    }

    function showPage(page) {
        currentPage = page;
        
        // Hide all Gakusensei
        allGakusensei.forEach(gakusensei => {
            gakusensei.style.display = 'none';
        });

        // Show Gakusensei for current page
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        for (let i = startIndex; i < endIndex && i < allGakusensei.length; i++) {
            allGakusensei[i].style.display = '';
        }

        // Update pagination
        const totalPages = Math.ceil(allGakusensei.length / itemsPerPage);
        const paginationInfo = document.querySelector('.pagination-info');
        const paginationList = document.querySelector('.pagination');
        updatePagination(totalPages, paginationInfo, paginationList);
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
});

// Approve payout for individual Gakusensei
function approvePayout(userId) {
    if (confirm('Are you sure you want to approve this payout? This action will record the payout and reset the earnings.')) {
        fetch('process_payout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                user_id: userId,
                action: 'single'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and refresh page
                const modal = bootstrap.Modal.getInstance(document.getElementById(`approveModal${userId}`));
                if (modal) {
                    modal.hide();
                }
                location.reload();
            } else {
                alert('Error processing payout: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing payout');
        });
    }
}

// Approve all payouts
function approveAllPayouts() {
    if (confirm('Are you sure you want to approve ALL pending payouts? This action cannot be undone.')) {
        fetch('process_payout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                action: 'all'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('All payouts have been approved successfully!');
                location.reload();
            } else {
                alert('Error processing payouts: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing payouts');
        });
    }
}