// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const topicSearch = document.getElementById('topicSearch');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const suggestedTopics = document.getElementById('suggestedTopics');
    const selectedTopics = document.getElementById('selectedTopics');
    const selectedTopicsInput = document.getElementById('selectedTopicsInput');
    const letsGoButton = document.getElementById('letsGoButton');
    const topicForm = document.getElementById('topicForm');
    
    // Store all topics data
    let allTopics = [];
    
    // Fetch topics from the server (could be passed via PHP instead)
    function fetchTopics() {
        // In a real implementation, you might get this via an AJAX call
        // For now, we'll extract from the DOM
        const topicElements = suggestedTopics.querySelectorAll('.topic-item');
        topicElements.forEach(element => {
            const topicId = element.getAttribute('data-topic-id');
            const topicName = element.querySelector('.topic-name').textContent;
            const topicIcon = element.querySelector('.topic-icon')?.src || '';
            
            allTopics.push({
                id: topicId,
                name: topicName,
                icon: topicIcon
            });
        });
    }
    
    // Initialize
    fetchTopics();
    
    // Search functionality
    topicSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        // Clear previous suggestions
        searchSuggestions.innerHTML = '';
        
        if (searchTerm.length < 2) {
            searchSuggestions.style.display = 'none';
            return;
        }
        
        // Filter topics based on search term
        const filteredTopics = allTopics.filter(topic => 
            topic.name.toLowerCase().includes(searchTerm)
        );
        
        if (filteredTopics.length > 0) {
            searchSuggestions.style.display = 'block';
            
            filteredTopics.forEach(topic => {
                const suggestionItem = document.createElement('div');
                suggestionItem.className = 'suggestion-item';
                suggestionItem.textContent = topic.name;
                suggestionItem.setAttribute('data-topic-id', topic.id);
                
                suggestionItem.addEventListener('click', function() {
                    addTopic(topic.id, topic.name, topic.icon);
                    topicSearch.value = '';
                    searchSuggestions.style.display = 'none';
                });
                
                searchSuggestions.appendChild(suggestionItem);
            });
        } else {
            searchSuggestions.style.display = 'none';
        }
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!topicSearch.contains(e.target) && !searchSuggestions.contains(e.target)) {
            searchSuggestions.style.display = 'none';
        }
    });
    
    // Add topic from suggestions
    suggestedTopics.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-topic') || e.target.parentElement.classList.contains('add-topic')) {
            const topicItem = e.target.closest('.topic-item');
            const topicId = topicItem.getAttribute('data-topic-id');
            const topicName = topicItem.querySelector('.topic-name').textContent;
            const topicIcon = topicItem.querySelector('.topic-icon')?.src || '';
            
            addTopic(topicId, topicName, topicIcon);
        }
    });
    
    // Add topic to selected list
    function addTopic(id, name, icon) {
        // Check if topic is already selected
        if (document.querySelector(`.selected-topic[data-topic-id="${id}"]`)) {
            return;
        }
        
        // Remove "no topics" message if it exists
        const noTopicsMsg = selectedTopics.querySelector('.no-topics');
        if (noTopicsMsg) {
            noTopicsMsg.remove();
        }
        
        // Create selected topic element
        const selectedTopic = document.createElement('div');
        selectedTopic.className = 'selected-topic';
        selectedTopic.setAttribute('data-topic-id', id);
        
        let topicContent = `<span class="selected-topic-name">${name}</span>`;
        topicContent += `<button type="button" class="remove-topic" data-topic-id="${id}">
                            <i class="fas fa-times"></i>
                         </button>`;
        
        selectedTopic.innerHTML = topicContent;
        selectedTopics.appendChild(selectedTopic);
        
        // Update hidden input
        updateSelectedTopicsInput();
        
        // Show the Let's Go button
        letsGoButton.style.display = 'block';
        
        // Add event listener for removal
        selectedTopic.querySelector('.remove-topic').addEventListener('click', function() {
            removeTopic(id);
        });
    }
    
    // Remove topic from selected list
    function removeTopic(id) {
        const topicToRemove = document.querySelector(`.selected-topic[data-topic-id="${id}"]`);
        if (topicToRemove) {
            topicToRemove.remove();
            
            // Update hidden input
            updateSelectedTopicsInput();
            
            // Hide Let's Go button if no topics selected
            if (selectedTopics.querySelectorAll('.selected-topic').length === 0) {
                letsGoButton.style.display = 'none';
                selectedTopics.innerHTML = '<p class="text-muted no-topics">No topics selected yet. Search or select from suggestions above.</p>';
            }
        }
    }
    
    // Update the hidden input with selected topic IDs
    function updateSelectedTopicsInput() {
        const selectedIds = Array.from(selectedTopics.querySelectorAll('.selected-topic'))
            .map(el => el.getAttribute('data-topic-id'));
        
        selectedTopicsInput.value = selectedIds.join(',');
    }
    
    // Form submission handling
    topicForm.addEventListener('submit', function(e) {
        const selectedIds = selectedTopicsInput.value.split(',').filter(id => id !== '');
        
        if (selectedIds.length === 0) {
            e.preventDefault();
            alert('Please select at least one topic.');
            return;
        }
    });
    
    // Page transition animation
    function navigateTo(url) {
        const overlay = document.querySelector('.transition-overlay');
        overlay.style.opacity = '1';
        
        setTimeout(() => {
            window.location.href = url;
        }, 300);
    }
    
    // Add event listeners for any navigation if needed
    const navLinks = document.querySelectorAll('a[data-navigate]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            navigateTo(this.href);
        });
    });
});