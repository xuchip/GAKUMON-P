// Admin CRUD Testing and Error Display Functions

// Test user creation
function testCreateUser() {
    const testData = {
        action: 'create_user',
        first_name: 'Test',
        last_name: 'User',
        username: 'testuser' + Date.now(),
        email_address: 'test' + + Date.now() + '@example.com',
        password: 'testpass123',
        role: 'Gakusei',
        subscription_type: 'Free',
        gakucoins: 100,
        is_verified: 1
    };

    fetch('admin_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(testData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Create User Test Result:', data);
        if (!data.success) {
            console.error('Create User Error:', data.message);
            if (data.debug) console.error('Debug Info:', data.debug);
        }
    })
    .catch(error => {
        console.error('Create User Request Failed:', error);
    });
}

// Test user retrieval - first get a valid user ID
function testGetUser() {
    // First get the users table to find a valid user ID
    fetch('admin_ajax.php?action=get_users_table')
    .then(response => response.text())
    .then(html => {
        // Extract first user ID from the HTML response
        const match = html.match(/onclick="editUser\((\d+)\)">/);
        const userId = match ? match[1] : 1;
        
        // Now test getting that user
        return fetch(`admin_ajax.php?action=get_user&user_id=${userId}`);
    })
    .then(response => response.json())
    .then(data => {
        console.log('Get User Test Result:', data);
        if (!data.success) {
            console.error('Get User Error:', data.message);
            if (data.debug) console.error('Debug Info:', data.debug);
        }
    })
    .catch(error => {
        console.error('Get User Request Failed:', error);
    });
}

// Test user update
function testUpdateUser(userId) {
    const testData = {
        action: 'update_user',
        user_id: userId,
        first_name: 'Updated',
        last_name: 'User',
        username: 'updateduser' + Date.now(),
        email_address: 'updated' + Date.now() + '@example.com',
        role: 'Gakusei',
        subscription_type: 'Premium',
        gakucoins: 200,
        is_verified: 1
    };

    fetch('admin_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(testData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update User Test Result:', data);
        if (!data.success) {
            console.error('Update User Error:', data.message);
            if (data.debug) console.error('Debug Info:', data.debug);
        }
    })
    .catch(error => {
        console.error('Update User Request Failed:', error);
    });
}

// Test lesson creation with proper duration format
function testCreateLesson() {
    // Get valid topic ID first
    return fetch('admin_ajax.php?action=get_chart_data')
    .then(response => response.json())
    .then(() => {
        const testData = {
            action: 'create_lesson',
            title: 'Test Lesson ' + Date.now(),
            short_desc: 'This is a test lesson',
            long_desc: 'This is a longer description of the test lesson',
            duration: '00:30:00', // Proper TIME format
            topic_id: window.testTopicId || 1, // Use created topic or default
            difficulty_level: 'Beginner',
            is_private: 0,
            author_id: '' // Leave empty to avoid foreign key issues
        };

        return fetch('admin_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(testData)
        });
    })
    .then(response => response.json())
    .then(data => {
        console.log('Create Lesson Test Result:', data);
        if (!data.success) {
            console.error('Create Lesson Error:', data.message);
            if (data.debug) console.error('Debug Info:', data.debug);
        } else {
            // Store lesson ID for quiz test
            window.testLessonId = data.lesson_id;
            console.log('Lesson created with ID:', data.lesson_id);
        }
        return data;
    })
    .catch(error => {
        console.error('Create Lesson Request Failed:', error);
        return { success: false };
    });
}

// Test quiz creation with valid lesson reference
function testCreateQuiz() {
    const testData = {
        action: 'create_quiz',
        title: 'Test Quiz ' + Date.now(),
        lesson_id: window.testLessonId || null, // Use created lesson ID or null
        is_ai_generated: 0,
        author_id: '' // Leave empty to avoid foreign key issues
    };

    fetch('admin_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(testData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Create Quiz Test Result:', data);
        if (!data.success) {
            console.error('Create Quiz Error:', data.message);
            if (data.debug) console.error('Debug Info:', data.debug);
        }
    })
    .catch(error => {
        console.error('Create Quiz Request Failed:', error);
    });
}

// Get valid test data from database
function getValidTestData() {
    return Promise.all([
        fetch('admin_ajax.php?action=get_users_table').then(r => r.text()),
        fetch('test_db_connection.php').then(r => r.json())
    ]).then(([usersHtml, dbInfo]) => {
        // Extract user ID from users table
        const userMatch = usersHtml.match(/onclick="editUser\((\d+)\)">/);
        const validUserId = userMatch ? userMatch[1] : null;
        
        return {
            validUserId,
            dbConnected: dbInfo.success
        };
    });
}

// Run all CRUD tests with valid data
function runAllCrudTests() {
    console.log('Starting CRUD Tests...');
    
    getValidTestData().then(testData => {
        console.log('Test Data:', testData);
        
        if (!testData.dbConnected) {
            console.error('Database not connected, skipping tests');
            return;
        }
        
        // Test user operations
        testCreateUser();
        
        setTimeout(() => {
            testGetUser(); // Test with dynamic user ID
        }, 1000);
        
        // Test lesson operations (first ensure topic exists)
        setTimeout(() => {
            ensureTestTopic().then((topicResult) => {
                if (topicResult.success) {
                    return testCreateLesson();
                } else {
                    console.error('Topic creation failed, skipping lesson test');
                    return { success: false };
                }
            }).then((lessonResult) => {
                // Test quiz operations (after lesson is created)
                if (lessonResult.success) {
                    setTimeout(() => {
                        testCreateQuiz();
                    }, 1000);
                    
                    // Test shop operations
                    setTimeout(() => {
                        testCreateShopItem();
                    }, 2000);
                    
                    // Test creator management (these need existing data)
                    setTimeout(() => {
                        testApproveApplication();
                        testRejectApplication();
                        testProcessPayout();
                    }, 3000);
                } else {
                    console.error('Lesson creation failed, skipping quiz test');
                }
            });
        }, 2000);
        
    }).catch(error => {
        console.error('Failed to get test data:', error);
    });
}

// Enhanced error display function
function displayCrudErrors() {
    // Override console.error to also display in page
    const originalError = console.error;
    console.error = function(...args) {
        originalError.apply(console, args);
        
        // Create error display element if it doesn't exist
        let errorDisplay = document.getElementById('crud-error-display');
        if (!errorDisplay) {
            errorDisplay = document.createElement('div');
            errorDisplay.id = 'crud-error-display';
            errorDisplay.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                background: #ff4444;
                color: white;
                padding: 10px;
                border-radius: 5px;
                max-width: 400px;
                z-index: 10000;
                font-family: monospace;
                font-size: 12px;
                max-height: 300px;
                overflow-y: auto;
            `;
            document.body.appendChild(errorDisplay);
        }
        
        const errorMsg = args.map(arg => 
            typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
        ).join(' ');
        
        errorDisplay.innerHTML += `<div style="border-bottom: 1px solid #fff; padding: 5px 0;">${new Date().toLocaleTimeString()}: ${errorMsg}</div>`;
        errorDisplay.scrollTop = errorDisplay.scrollHeight;
    };
}

// Ensure test topic exists
function ensureTestTopic() {
    const testData = {
        action: 'create_topic',
        topic_name: 'Test Topic ' + Date.now(),
        topic_icon: '📚'
    };

    return fetch('admin_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(testData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Ensure Topic Result:', data);
        if (data.success && data.topic_id) {
            window.testTopicId = data.topic_id;
        }
        return data;
    })
    .catch(error => {
        console.error('Topic Creation Failed:', error);
        return { success: false };
    });
}

// Test database connection
function testDatabaseConnection() {
    fetch('test_db_connection.php')
    .then(response => response.json())
    .then(data => {
        console.log('Database Connection Test:', data);
        if (!data.success) {
            console.error('Database Connection Error:', data.message);
            if (data.error_details) console.error('Error Details:', data.error_details);
        } else {
            console.log('Database Info:', {
                user_count: data.user_count,
                tables: data.tables,
                server_info: data.server_info
            });
        }
    })
    .catch(error => {
        console.error('Database Connection Request Failed:', error);
    });
}

// Initialize error display when page loads
document.addEventListener('DOMContentLoaded', function() {
    displayCrudErrors();
    
    // Add test buttons to page
    const buttonContainer = document.createElement('div');
    buttonContainer.style.cssText = `
        position: fixed;
        bottom: 10px;
        right: 10px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 5px;
    `;
    
    const dbTestButton = document.createElement('button');
    dbTestButton.textContent = 'Test DB Connection';
    dbTestButton.style.cssText = `
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
    `;
    dbTestButton.onclick = testDatabaseConnection;
    
    const crudTestButton = document.createElement('button');
    crudTestButton.textContent = 'Run CRUD Tests';
    crudTestButton.style.cssText = `
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
    `;
    crudTestButton.onclick = runAllCrudTests;
    
    buttonContainer.appendChild(dbTestButton);
    buttonContainer.appendChild(crudTestButton);
    document.body.appendChild(buttonContainer);
});