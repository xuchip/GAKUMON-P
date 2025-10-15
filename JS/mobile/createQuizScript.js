// ========== Safe account dropdown wiring (single source of truth) ==========
(function () {
  function wireDropdown() {
    const accountBtn = document.getElementById('accountDropdownBtn');
    const dropdown   = document.getElementById('accountDropdown');
    if (!accountBtn || !dropdown) return; // page doesn't have these

    accountBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      // re-check in case DOM mutates
      const btn  = document.getElementById('accountDropdownBtn');
      const menu = document.getElementById('accountDropdown');
      if (!btn || !menu) return;
      const outsideBtn  = e.target !== btn && !btn.contains(e.target);
      const outsideMenu = !menu.contains(e.target);
      if (outsideBtn && outsideMenu) menu.classList.remove('show');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wireDropdown);
  } else {
    wireDropdown();
  }
})();

// ======== Remove the duplicate global outside-click closer (it could throw) ========
// (deleted your second document.addEventListener('click', ...) block)

document.addEventListener('DOMContentLoaded', function () {
  console.log("Document loaded. Initializing editor...");

  // ---- tiny helpers
  const $id  = (id)  => document.getElementById(id);
  const $qs  = (sel) => document.querySelector(sel);
  const $qsa = (sel) => Array.from(document.querySelectorAll(sel));
  const on   = (el, type, fn) => el && el.addEventListener(type, fn);
  // Prefer the first selector that exists
    const one = (...sels) => sels.map(s => document.querySelector(s)).find(Boolean);


  let lastModalTrigger = null;


  // Hoisted helper (must appear before any function that calls it)
  function getTitleInput() {
    return document.querySelector('input[name="quizTitle"], input[name="quiz_title"]')
        || document.getElementById('headerLesson-title')
        || null;
  }

  // === Hoisted storage helpers (defined before use) ===
  function loadQuizFromStorage() {
    const saved = localStorage.getItem('gakumon_draft_quiz');
    if (!saved) return;
    try {
      const parsed = JSON.parse(saved);
      if (parsed && typeof parsed === 'object') {
        quizData = parsed;
        const titleInput = getTitleInput();
        if (titleInput) {
          quizData.lesson_title = titleInput.value?.trim() || quizData.lesson_title || 'New Quiz';
        }
      }
    } catch (e) {
      console.warn('Bad saved quiz, clearing.', e);
      quizData = { lesson_title: 'New Quiz', questions: [] };
    }
  }

  function saveQuizToStorage() {
    const titleInput = getTitleInput();
    if (titleInput) quizData.lesson_title = titleInput.value;
    localStorage.setItem('gakumon_draft_quiz', JSON.stringify(quizData));
  }

  // URL param helper
  const params  = new URLSearchParams(window.location.search);
  const isFresh = params.get('fresh');

  // hidden input helper
  function ensureHiddenInput(form, name) {
    if (!form) return null;
    let el = form.querySelector(`input[name="${name}"]`);
    if (!el) {
      el = document.createElement('input');
      el.type = 'hidden';
      el.name = name; // PHP reads by NAME
      form.appendChild(el);
    }
    return el;
  }

  // quiz state
  let quizData = { lesson_title: 'New Quiz', questions: [] };
  let currentSlideIndex = -1;

  // ================= MODAL (robust: Bootstrap OR custom) =================
    const findEl = (...sels) => sels.map(s => document.querySelector(s)).find(Boolean);

    // Try multiple selectors to locate your modal (covering desktop/mobile markup)
    const qtEl = findEl(
    '#questionTypeModal',
    '.question-type-modal',
    '[data-modal="question-type"]',
    '#questionType' // extra fallback (in case desktop used this id)
    );

    let bsQT = null;
    function ensureBsModal() {
    if (!qtEl) return null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        try {
        if (bootstrap.Modal.getOrCreateInstance) {
            bsQT = bootstrap.Modal.getOrCreateInstance(qtEl);
        } else {
            bsQT = bsQT || new bootstrap.Modal(qtEl);
        }
        // Ensure we return focus to the opener once hidden
        qtEl.addEventListener('hidden.bs.modal', () => {
            (lastModalTrigger || document.body).focus();
        }, { once: false });
        return bsQT;
        } catch {
        return null;
        }
    }
    return null;
    }

    function openQuestionTypeModal() {
    if (!qtEl) { console.warn('Question type modal element not found.'); return; }
    const inst = ensureBsModal();
    if (inst) {
        inst.show();
    } else {
        // Custom fallback: make sure your CSS shows .active or .show
        qtEl.classList.add('active', 'show');
        document.body.style.overflow = 'hidden';
    }
    }

    function closeQuestionTypeModal() {
    if (!qtEl) return;

    // Blur any focused control inside the modal to avoid aria-hidden focus warning
    if (document.activeElement && qtEl.contains(document.activeElement)) {
        document.activeElement.blur();
    }

    const inst = ensureBsModal();
    if (inst) {
        inst.hide();
        // focus will be restored by 'hidden.bs.modal' handler above
    } else {
        qtEl.classList.remove('active', 'show');
        document.body.style.overflow = '';
        // restore focus to the opener soon after we hide
        setTimeout(() => (lastModalTrigger || document.body).focus(), 0);
    }
    }

    // Optional close bindings for custom modal markup
    if (qtEl) {
    const closeBtns = qtEl.querySelectorAll('.custom-modal-close, .modal-close, [data-dismiss="modal"], [data-close="modal"]');
    closeBtns.forEach(btn => btn.addEventListener('click', closeQuestionTypeModal));

    const backdrop = qtEl.querySelector('.custom-modal-backdrop, .modal-backdrop-fake');
    if (backdrop) backdrop.addEventListener('click', closeQuestionTypeModal);

    const content = qtEl.querySelector('.custom-modal-content, .modal-content');
    if (content) content.addEventListener('click', (e) => e.stopPropagation());
    }

    // ESC to close for custom modal
    document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && qtEl && (qtEl.classList.contains('active') || qtEl.classList.contains('show'))) {
        closeQuestionTypeModal();
    }
    });


  // ================= RENDER / EDITOR =================
    function renderSlideList() {
    const slideList = one('#slide-list', '#mobile-slide-list', '[data-role="slide-list"]');
    if (!slideList) {
        console.warn('Slide list container not found.');
        saveQuizToStorage();
        return;
    }
    slideList.innerHTML = '';

    quizData.questions.forEach((q, idx) => {
        const div = document.createElement('div');
        div.className = `slide-item ${idx === currentSlideIndex ? 'active' : ''}`;
        div.dataset.index = idx;

        let typeLabel = '📝';
        if (q.question_type === 'multiple_choice') typeLabel = 'Multiple Choice';
        else if (q.question_type === 'true_false') typeLabel = 'True or False';
        else if (q.question_type === 'fill_blank') typeLabel = 'Fill in the Blank';
        else if (q.question_type === 'identification') typeLabel = 'Identification';

        div.innerHTML = `
        <div class="slide-number">${idx + 1}</div>
        <div class="slide-content">
            <div class="slide-type">${typeLabel}</div>
            <div class="slide-text">${(q.question_text || 'New Question')}</div>
        </div>
        `;
        div.addEventListener('click', () => showSlide(idx));
        slideList.appendChild(div);
    });

    // Update "total" counter here too
    const totalEl = one('#total-slides', '#mobile-total-slides', '[data-role="total-slides"]');
    if (totalEl) totalEl.textContent = String(quizData.questions.length);

    saveQuizToStorage();
    }

  function clearEditor() {
    const qt = $id('question-type');
    const qx = $id('question-text');
    const csn = $id('current-slide-num');
    const oc = $id('options-container');
    const cs = $id('current-slide');
    if (qt) qt.textContent = 'Select a Type';
    if (qx) qx.value = '';
    if (csn) csn.textContent = '0';
    if (oc) oc.innerHTML = '';
    if (cs) cs.textContent = '0';
    currentSlideIndex = -1;
  }

    function renderOptions(question) {
    const optionsContainer = one('#options-container', '#mobile-options-container', '[data-role="options-container"]');
    if (!optionsContainer) {
        console.warn('Options container not found.');
        return;
    }
    optionsContainer.innerHTML = '';

    if (question.question_type === 'multiple_choice' || question.question_type === 'true_false') {
        (question.options || []).forEach((option, optIndex) => {
        const row = document.createElement('div');
        row.className = 'option-row';
        row.innerHTML = `
            <input type="text" class="option-input form-control" value="${option.option_text || ''}"
            placeholder="Option text" data-opt-index="${optIndex}">
            <button type="button" class="correct-toggle ${option.is_correct ? 'correct' : ''}"
            data-opt-index="${optIndex}">
            ${option.is_correct ? 'Correct' : 'Mark Correct'}
            </button>
        `;
        optionsContainer.appendChild(row);
        });
    } else if (question.question_type === 'fill_blank' || question.question_type === 'identification') {
        const row = document.createElement('div');
        row.className = 'option-row';
        row.innerHTML = `
        <input type="text" class="option-input form-control" value="${question.correct_answer || ''}"
            placeholder="Correct answer" data-correct-answer="true">
        <button type="button" class="correct-toggle correct">Correct Answer</button>
        `;
        optionsContainer.appendChild(row);
    }

    attachOptionEventListeners(question);
    saveQuizToStorage();
    }


  function attachOptionEventListeners(question) {
    const qi = currentSlideIndex;
    $qsa('.option-input').forEach(input => {
      on(input, 'input', (e) => {
        if (question.question_type === 'fill_blank' || question.question_type === 'identification') {
          quizData.questions[qi].correct_answer = e.target.value;
        } else {
          const optIndex = Number(e.target.dataset.optIndex);
          if (!Number.isNaN(optIndex) && quizData.questions[qi].options?.[optIndex]) {
            quizData.questions[qi].options[optIndex].option_text = e.target.value;
          }
        }
        saveQuizToStorage();
      });
    });

    if (question.question_type === 'multiple_choice' || question.question_type === 'true_false') {
      $qsa('.correct-toggle').forEach(btn => {
        on(btn, 'click', (e) => {
          const optIndex = Number(e.currentTarget.dataset.optIndex);
          (quizData.questions[qi].options || []).forEach(opt => opt.is_correct = false);
          if (!Number.isNaN(optIndex) && quizData.questions[qi].options?.[optIndex]) {
            quizData.questions[qi].options[optIndex].is_correct = true;
          }
          renderOptions(quizData.questions[qi]); // will re-bind + save
        });
      });
    }
  }

    function showSlide(index) {
    if (index < 0 || index >= quizData.questions.length) {
        clearEditor();
        updateSlideCounter(); // keep header coherent if we cleared
        return;
    }
    currentSlideIndex = index;
    const q = quizData.questions[index];

    // Active class on whichever list exists
    (one('#slide-list', '#mobile-slide-list', '[data-role="slide-list"]')?.querySelectorAll('.slide-item') || [])
        .forEach((item, i) => item.classList.toggle('active', i === index));

    const qt = one('#question-type', '#mobile-question-type', '[data-role="question-type"]');
    const qx = one('#question-text', '#mobile-question-text', '[data-role="question-text"]');
    const csn = one('#current-slide-num', '#mobile-current-slide-num', '[data-role="current-slide-num"]');
    const cs  = one('#current-slide', '#mobile-current-slide', '[data-role="current-slide"]');

    if (qt) qt.textContent = q.question_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    if (qx) qx.value = q.question_text || '';
    if (csn) csn.textContent = String(index + 1);
    if (cs)  cs.textContent  = String(index + 1);

    renderOptions(q);
    updateSlideCounter(); // <-- ensure "Question X of Y" is correct
    }


  function addNewOption() {
    const q = quizData.questions[currentSlideIndex];
    if (!q || !Array.isArray(q.options)) return;
    q.options.push({ option_text: '', is_correct: false });
    renderOptions(q);
  }

  function addNewQuestion(type) {
    const base = { question_id: Date.now(), question_text: '', question_type: type };
    if (type === 'multiple_choice') {
      base.options = [
        { option_text: '', is_correct: true },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
      ];
    } else if (type === 'true_false') {
      base.options = [
        { option_text: 'True',  is_correct: true  },
        { option_text: 'False', is_correct: false },
      ];
    } else {
      base.correct_answer = '';
    }
    quizData.questions.push(base);
    currentSlideIndex = quizData.questions.length - 1;
    renderSlideList();
    showSlide(currentSlideIndex);
    updateSlideCounter();

  }

  function deleteCurrentQuestion() {
    if (quizData.questions.length <= 1) {
      alert('You must have at least one question.');
      return;
    }
    if (!confirm('Are you sure you want to delete this question?')) return;
    quizData.questions.splice(currentSlideIndex, 1);
    if (currentSlideIndex >= quizData.questions.length) {
      currentSlideIndex = Math.max(0, quizData.questions.length - 1);
    }
    renderSlideList();
    quizData.questions.length ? showSlide(currentSlideIndex) : clearEditor();
  }

    function updateSlideCounter() {
    const cs = one('#current-slide', '#mobile-current-slide', '[data-role="current-slide"]');
    const ts = one('#total-slides', '#mobile-total-slides', '[data-role="total-slides"]');
    if (cs) cs.textContent = String(currentSlideIndex + 1);
    if (ts) ts.textContent = String(quizData.questions.length);
    }

  // ---- elements
  const quizEditorForm = $id('quizEditorForm');
  const saveBtn        = $id('saveQuizBtn');

  // prevent natural submit if form exists
  on(quizEditorForm, 'submit', (e) => e.preventDefault());

  // real save via button
  on(saveBtn, 'click', (e) => {
    e.preventDefault();
    if (!quizEditorForm) return;

    const titleInput = getTitleInput();
    if (titleInput) quizData.lesson_title = titleInput.value?.trim() || 'Untitled Quiz';

    const lessonId = params.get('lesson_id');
    if (lessonId) quizData.lesson_id = Number(lessonId);

    ensureHiddenInput(quizEditorForm, 'quizDataJson')?.setAttribute('value', JSON.stringify(quizData));
    ensureHiddenInput(quizEditorForm, 'quiz_title')?.setAttribute('value', quizData.lesson_title || 'Untitled Quiz');
    if (quizData.lesson_id) ensureHiddenInput(quizEditorForm, 'lesson_id')?.setAttribute('value', String(quizData.lesson_id));

    localStorage.removeItem('gakumon_draft_quiz');
    HTMLFormElement.prototype.submit.call(quizEditorForm);
  });

    // ================= OPENERS & CONTROLS (guarded) =================

    // Button exists in your HTML as: <button id="mobile-add-slide" class="mobile-add-btn">...</button>
    const addSlideBtn =
    $id('mobile-add-slide') ||   // <-- your mobile opener
    $qs('.mobile-add-btn') ||
    $id('add-slide-btn') ||
    $id('addQuestionBtn') ||
    $qs('[data-action="add-question"]') ||
    $qs('[data-bs-target="#questionTypeModal"]') ||
    $qs('[data-target="#questionTypeModal"]');

    if (addSlideBtn) {
    const hasBsDataToggle = addSlideBtn.matches('[data-bs-toggle="modal"], [data-toggle="modal"]') &&
                            (addSlideBtn.getAttribute('data-bs-target') || addSlideBtn.getAttribute('data-target'));
    const bsAvailable = (typeof bootstrap !== 'undefined' && bootstrap.Modal);

    if (hasBsDataToggle && bsAvailable) {
        console.debug('Add button: using Bootstrap data-API to open modal.');
        // When Bootstrap opens it, remember trigger so we can restore focus on close
        addSlideBtn.addEventListener('click', () => { lastModalTrigger = addSlideBtn; });
        // Do NOT preventDefault; Bootstrap handles the show()
    } else {
        addSlideBtn.addEventListener('click', (e) => {
        e.preventDefault();
        lastModalTrigger = addSlideBtn; // remember opener
        console.debug('Add button: opening via fallback.');
        openQuestionTypeModal();
        });
    }
    } else {
    console.warn('Add New Question button not found. Check selectors.');
    }

    // Choosing a type closes the modal and adds a question
    $qsa('.question-type-option, [data-question-type]').forEach(opt =>
    on(opt, 'click', () => {
        const type = opt.dataset.type || opt.dataset.questionType;
        if (!type) return;
        closeQuestionTypeModal();
        addNewQuestion(type);
    })
    );

    on($id('prev-slide'), 'click', () => currentSlideIndex > 0 && showSlide(currentSlideIndex - 1));
    on($id('next-slide'), 'click', () => currentSlideIndex < quizData.questions.length - 1 && showSlide(currentSlideIndex + 1));
    on($id('delete-question'), 'click', deleteCurrentQuestion);



  on($id('question-text'), 'input', (e) => {
    if (currentSlideIndex < 0) return;
    quizData.questions[currentSlideIndex].question_text = e.target.value;
    saveQuizToStorage();
    const items = $qsa('.slide-item');
    const slideText = items[currentSlideIndex]?.querySelector('.slide-text');
    if (slideText) slideText.textContent = e.target.value || 'New Question';
  });

  const titleInput = getTitleInput();
  on(titleInput, 'input', (e) => {
    quizData.lesson_title = e.target.value;
    saveQuizToStorage();
  });

  // ================= INIT =================
  if (isFresh) localStorage.removeItem('gakumon_draft_quiz');
  else loadQuizFromStorage();

  renderSlideList();
  if (quizData.questions.length > 0) showSlide(0);
  else clearEditor();

  updateSlideCounter();
  console.log("Editor initialization complete.");
});
