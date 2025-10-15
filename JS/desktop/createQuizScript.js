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

document.addEventListener('DOMContentLoaded', function() {
    console.log("Document loaded. Initializing editor...");

    // For the addQuizBtn
    const params = new URLSearchParams(window.location.search);
    const isFresh = params.get('fresh');

    // Add this helper near the top of the DOMContentLoaded block
    function ensureHiddenInput(form, name) {
    let el = form.querySelector(`input[name="${name}"]`);
    if (!el) {
        el = document.createElement('input');
        el.type = 'hidden';
        el.name = name;              // <-- PHP reads by NAME, not id
        form.appendChild(el);
    }
    return el;
    }

    // Initialize an empty quiz structure
    let quizData = {
        lesson_title: 'New Quiz', // Default title
        questions: [] // Start with NO questions
    };

    let currentSlideIndex = -1; // Start with no selected slide
    const questionTypeModal = new bootstrap.Modal(document.getElementById('questionTypeModal'));

    // Load quiz from localStorage if it exists
    function loadQuizFromStorage() {
        console.log("Attempting to load quiz from localStorage...");
        const savedQuiz = localStorage.getItem('gakumon_draft_quiz');
        if (savedQuiz) {
            try {
                console.log("Found saved quiz:", savedQuiz);
                quizData = JSON.parse(savedQuiz);
                console.log("Parsed quiz data:", quizData);
                // Update the title input field
                const titleInput = getTitleInput();
                    if (titleInput) {
                        quizData.lesson_title = titleInput.value?.trim() || 'Untitled Quiz';
                    }
            } catch (e) {
                console.error("Error loading saved quiz:", e);
                // If parsing fails, keep the empty quizData
                quizData = { lesson_title: 'New Quiz', questions: [] };
            }
        } else {
            console.log("No saved quiz found in localStorage.");
        }
    }

    function getTitleInput() {
        return document.querySelector('input[name="quizTitle"], input[name="quiz_title"]')
            || document.getElementById('headerLesson-title');
    }

    // Save the current quiz state to localStorage
    function saveQuizToStorage() {
        console.log("Saving quiz to localStorage...");
        // Update the title from the input field before saving
        const titleInput = getTitleInput();
        if (titleInput) {
            quizData.lesson_title = titleInput.value;
        }
        const quizString = JSON.stringify(quizData);
        localStorage.setItem('gakumon_draft_quiz', quizString);
        console.log("Quiz saved:", quizString);
    }

    // Initialize the editor
    // function initEditor() {
    //     loadQuizFromStorage(); // Load any saved data first
    //     renderSlideList();

    //     // If there are questions, show the first one. Otherwise, the editor area stays empty.
    //     if (quizData.questions.length > 0) {
    //         console.log("Questions found, showing first slide.");
    //         showSlide(0);
    //     } else {
    //         console.log("No questions found, clearing editor.");
    //         // Clear the editor area if no questions
    //         clearEditor();
    //     }
    //     updateSlideCounter();
    // }
    // TRY initEditor
    function initEditor() {
        // If this is a fresh create, ensure no leftovers exist
        if (isFresh) {
        localStorage.removeItem('gakumon_draft_quiz');
        } else {
        loadQuizFromStorage(); // existing function
        }

        renderSlideList();

        if (quizData.questions.length > 0) {
        showSlide(0);
        } else {
        clearEditor();
        }
        updateSlideCounter();
    }

    // Render the slide list in the left panel
    function renderSlideList() {
        console.log("Rendering slide list for", quizData.questions.length, "questions");
        const slideList = document.getElementById('slide-list');
        slideList.innerHTML = '';

        quizData.questions.forEach((question, index) => {
            const slideItem = document.createElement('div');
            slideItem.className = `slide-item ${index === currentSlideIndex ? 'active' : ''}`;
            slideItem.dataset.index = index;

            let typeIcon = '📝';
            if (question.question_type === 'multiple_choice') typeIcon = 'Multiple Choice';
            else if (question.question_type === 'true_false') typeIcon = 'True or False';
            else if (question.question_type === 'fill_blank') typeIcon = 'Fill in the Blank';
            else if (question.question_type === 'identification') typeIcon = 'Identification';

            slideItem.innerHTML = `
                <div class="slide-number">${index + 1}</div>
                <div class="slide-content">
                    <div class="slide-type">${typeIcon}</div>
                </div>
            `;

            slideItem.addEventListener('click', () => showSlide(index));
            slideList.appendChild(slideItem);
        });

        document.getElementById('total-slides').textContent = quizData.questions.length;
        // Save state after rendering the list
        saveQuizToStorage();
    }

    // Clear the editor area (when no questions exist)
    function clearEditor() {
        console.log("Clearing editor area.");
        document.getElementById('question-type').textContent = 'Select a Type';
        document.getElementById('question-text').value = '';
        document.getElementById('current-slide-num').textContent = '0';
        document.getElementById('options-container').innerHTML = '';
        document.getElementById('current-slide').textContent = '0';
        currentSlideIndex = -1;
    }

    // Show a specific slide
    function showSlide(index) {
        console.log("Showing slide:", index);
        // Check if the index is valid
        if (index < 0 || index >= quizData.questions.length) {
            console.log("Invalid slide index, clearing editor.");
            clearEditor();
            return;
        }

        currentSlideIndex = index;
        const question = quizData.questions[index];
        console.log("Slide data:", question);

        // Update active slide in the list
        document.querySelectorAll('.slide-item').forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });

        // Update editor content
        document.getElementById('question-type').textContent =
            question.question_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        document.getElementById('question-text').value = question.question_text;
        document.getElementById('current-slide-num').textContent = index + 1;
        document.getElementById('current-slide').textContent = index + 1;

        // Render options based on question type
        renderOptions(question);
    }

    // Render options based on question type
    function renderOptions(question) {
        console.log("Rendering options for question type:", question.question_type);
        const optionsContainer = document.getElementById('options-container');
        optionsContainer.innerHTML = '';

        if (question.question_type === 'multiple_choice' || question.question_type === 'true_false') {
            console.log("Rendering MC/TF options:", question.options);
            question.options.forEach((option, optIndex) => {
                const optionRow = document.createElement('div');
                optionRow.className = 'option-row';
                optionRow.innerHTML = `
                    <input type="text" class="option-input form-control" value="${option.option_text || ''}"
                        placeholder="Option text" data-opt-index="${optIndex}">
                    <button type="button" class="correct-toggle ${option.is_correct ? 'correct' : ''}"
                        data-opt-index="${optIndex}">
                        ${option.is_correct ? 'Correct' : 'Mark Correct'}
                    </button>
                `;
                optionsContainer.appendChild(optionRow);
            });

        } else if (question.question_type === 'fill_blank' || question.question_type === 'identification') {
            console.log("Rendering FIB/Identification answer:", question.correct_answer);
            const answerInput = document.createElement('div');
            answerInput.className = 'option-row';
            answerInput.innerHTML = `
                <input type="text" class="option-input form-control" value="${question.correct_answer || ''}"
                    placeholder="Correct answer" data-correct-answer="true">
                <button type="button" class="correct-toggle correct">Correct Answer</button>
            `;
            optionsContainer.appendChild(answerInput);
        }

        // Add event listeners for the newly created inputs and buttons
        attachOptionEventListeners(question);
        // Save state after rendering new options
        saveQuizToStorage();
    }

    // Attach event listeners to options
    function attachOptionEventListeners(question) {
        const questionIndex = currentSlideIndex; // Capture current index for closure
        console.log("Attaching listeners for question index:", questionIndex);

        // Input change events - Update the data model on every input
        document.querySelectorAll('.option-input').forEach(input => {
            input.addEventListener('input', (e) => { // Use 'input' event for real-time update
                console.log("Option input changed:", e.target.value);
                if (question.question_type === 'fill_blank' || question.question_type === 'identification') {
                    quizData.questions[questionIndex].correct_answer = e.target.value;
                    console.log("Updated correct answer to:", e.target.value);
                } else {
                    const optIndex = parseInt(e.target.dataset.optIndex);
                    quizData.questions[questionIndex].options[optIndex].option_text = e.target.value;
                    console.log("Updated option", optIndex, "to:", e.target.value);
                }
                saveQuizToStorage(); // Save on every change
            });
        });

        // Correct toggle events for MC/TF
        if (question.question_type === 'multiple_choice' || question.question_type === 'true_false') {
            document.querySelectorAll('.correct-toggle').forEach(button => {
                button.addEventListener('click', (e) => {
                    const optIndex = parseInt(e.target.dataset.optIndex);
                    console.log("Marking option", optIndex, "as correct");
                    // Update all options in this question
                    quizData.questions[questionIndex].options.forEach(opt => {
                        opt.is_correct = false;
                    });
                    quizData.questions[questionIndex].options[optIndex].is_correct = true;

                    // Re-render options to update the UI
                    renderOptions(quizData.questions[questionIndex]);
                    // saveQuizToStorage() is called inside renderOptions
                });
            });
        }
    }

    // Add a new option to a multiple choice question
    function addNewOption() {
        console.log("Adding new option to question:", currentSlideIndex);
        const question = quizData.questions[currentSlideIndex];
        question.options.push({
            option_text: '',
            is_correct: false
        });
        renderOptions(question); // Re-render will save state
    }

    // Add a new question/slide
    function addNewQuestion(type) {
        console.log("Adding new question of type:", type);
        const baseQuestion = {
            question_id: Date.now(), // Use timestamp as simple ID
            question_text: '',
            question_type: type,
        };

        // Set up structure based on type
        if (type === 'multiple_choice') {
            baseQuestion.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false }
            ];
        } else if (type === 'true_false') {
            baseQuestion.options = [
                { option_text: 'True', is_correct: true },
                { option_text: 'False', is_correct: false }
            ];
        } else {
            baseQuestion.correct_answer = '';
        }

        quizData.questions.push(baseQuestion);
        currentSlideIndex = quizData.questions.length - 1;
        renderSlideList();
        showSlide(currentSlideIndex);
        // State is saved by renderSlideList and showSlide -> renderOptions
    }

    // Delete current question
    function deleteCurrentQuestion() {
        console.log("Attempting to delete question:", currentSlideIndex);
        if (quizData.questions.length <= 1) {
            alert('You must have at least one question.');
            return;
        }

        if (confirm('Are you sure you want to delete this question?')) {
            quizData.questions.splice(currentSlideIndex, 1);
            // Adjust current slide index
            if (currentSlideIndex >= quizData.questions.length) {
                currentSlideIndex = Math.max(0, quizData.questions.length - 1);
            }
            renderSlideList();
            // Show the new current slide (or clear editor if last one was deleted)
            if (quizData.questions.length > 0) {
                showSlide(currentSlideIndex);
            } else {
                clearEditor();
            }
        }
    }

    // Update slide counter
    function updateSlideCounter() {
        document.getElementById('current-slide').textContent = currentSlideIndex + 1;
        document.getElementById('total-slides').textContent = quizData.questions.length;
    }

    // ---- inside DOMContentLoaded ----
    const quizEditorForm = document.getElementById('quizEditorForm');
    const saveBtn = document.getElementById('saveQuizBtn');

    // 1) REVERT: Block any accidental/natural submits so UI keeps working (e.g., Add Question modal)
    quizEditorForm.addEventListener('submit', function (e) {
    e.preventDefault(); // ← this is the "old" behavior when your modal still worked
    // (No navigation happens here.)
    });

    // 2) REAL save path lives on the Save button
    if (saveBtn) {
    saveBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // ensure title & (optional) lesson_id are captured
        const titleInput = getTitleInput();
        if (titleInput) quizData.lesson_title = titleInput.value?.trim() || 'Untitled Quiz';

        const params = new URLSearchParams(window.location.search);
        const lessonId = params.get('lesson_id');
        if (lessonId) quizData.lesson_id = Number(lessonId);

        // write JSON + title to named hidden fields (so PHP can read them)
        const json = JSON.stringify(quizData);

        // guarantee PHP sees these by NAME
        ensureHiddenInput(quizEditorForm, 'quizDataJson').value = json;
        ensureHiddenInput(quizEditorForm, 'quiz_title').value   = quizData.lesson_title || 'Untitled Quiz';

        // optional: if lesson_id exists, also pass it explicitly (harmless for standalone)
        if (quizData.lesson_id) {
        ensureHiddenInput(quizEditorForm, 'lesson_id').value = String(quizData.lesson_id);
        }

        // clear draft, then submit
        localStorage.removeItem('gakumon_draft_quiz');
        HTMLFormElement.prototype.submit.call(quizEditorForm);
    });
    }


    // Event listeners for buttons
    document.getElementById('add-slide-btn').addEventListener('click', () => {
        console.log("Add slide button clicked.");
        questionTypeModal.show();
    });

    document.querySelectorAll('.question-type-option').forEach(option => {
        option.addEventListener('click', () => {
            const type = option.dataset.type;
            console.log("Question type selected:", type);
            questionTypeModal.hide();
            addNewQuestion(type);
        });
    });

    document.getElementById('prev-slide').addEventListener('click', () => {
        console.log("Previous slide button clicked.");
        if (currentSlideIndex > 0) showSlide(currentSlideIndex - 1);
    });

    document.getElementById('next-slide').addEventListener('click', () => {
        console.log("Next slide button clicked.");
        if (currentSlideIndex < quizData.questions.length - 1) showSlide(currentSlideIndex + 1);
    });

    document.getElementById('delete-question').addEventListener('click', deleteCurrentQuestion);

    // Question text change event - update model on input
    document.getElementById('question-text').addEventListener('input', (e) => {
        if (currentSlideIndex >= 0) { // Check if a slide is selected
            console.log("Question text changed:", e.target.value);
            quizData.questions[currentSlideIndex].question_text = e.target.value;
            saveQuizToStorage();
            // Also update the preview text in the slide list
            const slideItems = document.querySelectorAll('.slide-item');
            if (slideItems[currentSlideIndex]) {
                const slideText = slideItems[currentSlideIndex].querySelector('.slide-text');
                if (slideText) {
                    slideText.textContent = e.target.value || 'New Question';
                }
            }
        }
    });

    // Quiz title change event
    const titleInput = getTitleInput();
    if (titleInput) {
        titleInput.addEventListener('input', (e) => {
            console.log("Quiz title changed:", e.target.value);
            quizData.lesson_title = e.target.value;
            saveQuizToStorage();
        });
    }

    // Initialize the editor
    console.log("Starting editor initialization...");
    initEditor();
    console.log("Editor initialization complete.");
});