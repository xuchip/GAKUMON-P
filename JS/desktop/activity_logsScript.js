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