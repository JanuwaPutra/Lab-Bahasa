<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Virtual Tutor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="page-header">
                <h2>{{ __('AI Language Tutor') }}</h2>
                <p class="lead">
                    {{ __('Practice your speaking and writing skills with our AI tutor that gives you instant feedback.') }}
                </p>
            </div>

            <div class="row g-3">
                <!-- Left Panel - Settings -->
                <div class="col-md-3">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('Settings') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="language-select" class="form-label">{{ __('Language:') }}</label>
                                <select id="language-select" class="form-select">
                                    @foreach($languages as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="level-select" class="form-label">{{ __('Language Level:') }}</label>
                                <select id="level-select" class="form-select">
                                    <option value="beginner" selected>{{ __('Beginner') }}</option>
                                    <option value="intermediate">{{ __('Intermediate') }}</option>
                                    <option value="advanced">{{ __('Advanced') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('Exercise Type') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="exercise-tab active" data-exercise="free_conversation">
                                <i class="bi bi-chat-dots"></i> {{ __('Free Conversation') }}
                            </div>
                            <div class="exercise-tab" data-exercise="writing_exercise">
                                <i class="bi bi-pencil"></i> {{ __('Writing Exercise') }}
                            </div>
                            <div class="exercise-tab" data-exercise="speaking_practice">
                                <i class="bi bi-mic"></i> {{ __('Speaking Practice') }}
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                {{ __('Topics') }} <span id="topic-type-label">{{ __('Conversation') }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="topic-container">
                                <!-- Topics will be loaded dynamically -->
                                <div class="d-flex justify-content-center p-2">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Panel - Chat and Input -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="mb-0" id="chat-title">{{ __('Free Conversation') }}</h5>
                                <span class="language-level-badge" id="language-badge">
                                    EN - {{ __('Beginner') }}
                                </span>
                            </div>
                            <button id="reset-chat" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i> {{ __('Reset') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="chat-container" class="chat-container mb-3">
                                <!-- Initial welcome message will be added by JavaScript -->
                            </div>

                            <div id="feedback-container" class="writing-feedback mb-3" style="display: none">
                                <!-- Feedback will be filled dynamically -->
                            </div>

                            <div class="d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <textarea id="user-input" class="form-control" 
                                        placeholder="{{ __('Type your message here...') }}" rows="2"></textarea>
                                </div>
                                <button id="voice-toggle" class="voice-record-button">
                                    <i class="bi bi-mic"></i>
                                </button>
                                <button id="send-button" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i> {{ __('Send') }}
                                </button>
                            </div>

                            <div id="recording-status" class="mt-2 mx-auto text-center" style="display: none">
                                <i class="bi bi-record-circle-fill text-danger"></i>
                                <span>{{ __('Recording') }}</span>
                                <span id="recording-time">00:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Instructions and Tips -->
                <div class="col-md-3">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('Instructions') }}</h5>
                        </div>
                        <div class="card-body">
                            <div id="instructions-container">
                                <!-- Instructions will be filled dynamically -->
                                <p><strong>{{ __('Free Conversation') }}</strong></p>
                                <ul>
                                    <li>{{ __('Start a conversation with the virtual tutor') }}</li>
                                    <li>{{ __('The tutor will adapt to your language level') }}</li>
                                    <li>{{ __('You will receive gentle corrections when making mistakes') }}</li>
                                    <li>{{ __('Choose a topic to start a discussion') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('Tips') }}</h5>
                        </div>
                        <div class="card-body">
                            <div id="tips-container">
                                <!-- Tips will be filled dynamically -->
                                <div class="d-flex gap-2 mb-2">
                                    <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                                    <p class="mb-0">
                                        <strong>{{ __('Tip:') }}</strong> {{ __('Use the suggested topics to start a more focused conversation.') }}
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                                    <p class="mb-0">
                                        <strong>{{ __('Tip:') }}</strong> {{ __('If you have difficulty, you can ask the tutor to provide examples or further explanation.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    :root {
        --primary-color: #4f46e5;
        --primary-hover: #4338ca;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-bg: #f9fafb;
        --card-bg: #ffffff;
        --border-radius: 12px;
    }

    /* Card header titles */
    .card-header h5 {
        font-size: 1rem !important;
    }

    /* Form elements */
    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .form-select, .form-control {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .form-select-lg {
        font-size: 1rem;
        padding: 0.5rem 1rem;
        height: auto;
    }

    /* Buttons */
    .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    /* Chat elements */
    .message {
        font-size: 1rem;
        padding: 0.625rem 0.875rem;
        max-width: 90%;
    }

    /* Other UI elements */
    .exercise-tab {
        cursor: pointer;
        padding: 0.75rem;
        border-radius: 0.75rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .exercise-tab:hover {
        background-color: #f5f5f5;
    }

    .exercise-tab.active {
        background-color: #eef2ff;
        color: var(--primary-color);
        font-weight: 500;
    }

    .exercise-tab i {
        font-size: 1.125rem;
    }

    .topic-card {
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 0.625rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        background-color: #f9fafb;
    }

    .topic-card:hover {
        background-color: #eef2ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    /* Typing indicator */
    .typing-indicator span {
        width: 6px;
        height: 6px;
    }
    
    /* Adjust spacing */
    .card-body {
        padding: 1rem !important;
    }
    
    .page-header {
        margin-bottom: 1.5rem;
    }
    


    .container-fluid {
        margin: 0 auto;
    }

    .card {
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
    }

    .card-header {
        background-color: var(--card-bg);
        border-bottom: 1px solid #e5e7eb;
        padding: 0.75rem 1rem !important;
        border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        font-weight: 600;
    }

    .chat-container {
        height: 430px;
        overflow-y: auto;
        padding: 1rem;
        background-color: var(--light-bg);
        border-radius: var(--border-radius);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .chat-container::-webkit-scrollbar {
        width: 6px;
    }

    .chat-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .chat-container::-webkit-scrollbar-thumb {
        background: #c7c7c7;
        border-radius: 10px;
    }

    .chat-container::-webkit-scrollbar-thumb:hover {
        background: #a0a0a0;
    }

    .typing-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .typing-indicator span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #a0aec0;
        display: inline-block;
        animation: bounce 1.4s infinite ease-in-out both;
    }

    .typing-indicator span:nth-child(1) {
        animation-delay: -0.32s;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes bounce {
        0%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-6px);
        }
    }

    /* Message styling */
    .message {
        padding: 0.625rem 0.875rem;
        border-radius: 0.75rem;
        max-width: 90%;
        word-wrap: break-word;
        line-height: 1.4;
        position: relative;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .user-message {
        background-color: var(--primary-color);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 0.25rem;
    }

    .assistant-message {
        background-color: #e2e8f0;
        align-self: flex-start;
        border-bottom-left-radius: 0.25rem;
    }

    .writing-feedback {
        background-color: #eef2ff;
        border-left: 4px solid var(--primary-color);
        padding: 1rem;
        margin-top: 1rem;
        border-radius: 0.5rem;
        font-size: 0.95rem;
        line-height: 1.6;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .writing-feedback h4 {
        color: var(--primary-color);
        margin-bottom: 0.75rem;
        font-size: 1.1rem;
    }

    .writing-feedback ul {
        padding-left: 1.5rem;
    }

    .writing-feedback p {
        margin-bottom: 0.75rem;
    }

    .voice-record-button {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        border: none;
    }

    .voice-record-button:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
    }

    .voice-record-button.recording {
        animation: pulse 1.5s infinite;
        background-color: var(--danger-color);
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        }
        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
        }
    }

    #send-button {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    #send-button:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
    }

    #user-input {
        border-radius: 0.5rem;
        padding: 0.625rem;
        resize: none;
        transition: border-color 0.2s ease;
        font-size: 1rem;
    }

    #user-input:focus {
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        border-color: var(--primary-color);
    }

    #reset-chat {
        border-radius: 0.5rem;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .page-header h2 {
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.375rem;
    }

    .page-header p {
        color: #6b7280;
    }

    #recording-status {
        padding: 0.375rem 0.75rem;
        background-color: #fee2e2;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
    }

    #recording-time {
        font-weight: 600;
    }

    .language-level-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        background-color: var(--success-color);
        color: white;
    }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // UI Elements
        const languageSelect = document.getElementById("language-select");
        const levelSelect = document.getElementById("level-select");
        const exerciseTabs = document.querySelectorAll(".exercise-tab");
        const chatTitle = document.getElementById("chat-title");
        const languageBadge = document.getElementById("language-badge");
        const topicContainer = document.getElementById("topic-container");
        const topicTypeLabel = document.getElementById("topic-type-label");
        const chatContainer = document.getElementById("chat-container");
        const userInput = document.getElementById("user-input");
        const sendButton = document.getElementById("send-button");
        const resetButton = document.getElementById("reset-chat");
        const voiceToggle = document.getElementById("voice-toggle");
        const recordingStatus = document.getElementById("recording-status");
        const recordingTime = document.getElementById("recording-time");
        const feedbackContainer = document.getElementById("feedback-container");
        const instructionsContainer = document.getElementById("instructions-container");
        const tipsContainer = document.getElementById("tips-container");

        // State
        let currentLanguage = languageSelect.value;
        let currentLevel = levelSelect.value;
        let currentExercise = "free_conversation";
        let isRecording = false;
        let recordingInterval = null;
        let recordingSeconds = 0;
        let mediaRecorder = null;
        let audioChunks = [];
        let conversationHistory = [];
        let sessionId = "{{ $userData['session_id'] ?? '' }}";
        let isAuthenticated = {{ Auth::check() ? 'true' : 'false' }};
        let visibilityPaused = false;

        // Handle tab visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                visibilityPaused = true;
            } else {
                visibilityPaused = false;
                // Force UI update when tab becomes visible again
                setTimeout(() => {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }, 100);
            }
        });

        // Load conversation history from server if user is authenticated
        function loadConversationHistory() {
            if (!isAuthenticated) {
                addWelcomeMessage();
                return;
            }

            // Show loading indicator
            chatContainer.innerHTML = `
                <div class="d-flex justify-content-center my-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            // Fetch conversation history from server
            fetch(`/virtual-tutor/history?session_id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history && data.history.length > 0) {
                        // Update session ID
                        sessionId = data.session_id;
                        
                        // Update language and level selectors based on the conversation
                        if (data.language) {
                            currentLanguage = data.language;
                            languageSelect.value = data.language;
                        }
                        
                        if (data.level) {
                            currentLevel = data.level;
                            levelSelect.value = data.level;
                        }
                        
                        if (data.exercise_type) {
                            currentExercise = data.exercise_type;
                            exerciseTabs.forEach(tab => {
                                if (tab.dataset.exercise === data.exercise_type) {
                                    tab.classList.add('active');
                                } else {
                                    tab.classList.remove('active');
                                }
                            });
                        }
                        
                        // Update UI
                        updateLanguageBadge();
                        updateInstructions();
                        fetchTopics();
                        
                        // Clear chat container
                        chatContainer.innerHTML = "";
                        
                        // Load conversation history
                        conversationHistory = data.history;
                        
                        // Display messages
                        conversationHistory.forEach(entry => {
                            const messageElement = document.createElement("div");
                            messageElement.classList.add("message");
                            
                            if (entry.role === "user") {
                                messageElement.classList.add("user-message");
                                messageElement.innerHTML = entry.content.replace(/\n/g, "<br>");
                            } else {
                                messageElement.classList.add("assistant-message");
                                messageElement.innerHTML = entry.content.replace(/\n/g, "<br>");
                            }
                            
                            chatContainer.appendChild(messageElement);
                        });
                        
                        // Load feedback if available
                        if (data.feedback) {
                            displaySavedFeedback(data.feedback);
                        } else {
                            feedbackContainer.style.display = "none";
                        }
                        
                        // Scroll to bottom
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    } else {
                        // No history found, add welcome message
                        addWelcomeMessage();
                    }
                })
                .catch(error => {
                    console.error("Error loading conversation history:", error);
                    // Add welcome message in case of error
                    addWelcomeMessage();
                });
        }

        // Add welcome message
        function addWelcomeMessage() {
            // Clear chat container
            chatContainer.innerHTML = "";
            
            let welcomeMessage = "Hello! I am your virtual tutor. What would you like to learn today?";
            
            // Set welcome message based on language
            if (currentLanguage === "id") {
                welcomeMessage = "Halo! Saya tutor virtual Anda. Apa yang ingin Anda pelajari hari ini?";
            } else if (currentLanguage === "ja") {
                welcomeMessage = "こんにちは！私はあなたの仮想チューターです。今日は何を学びたいですか？";
            } else if (currentLanguage === "ko") {
                welcomeMessage = "안녕하세요! 저는 당신의 가상 튜터입니다. 오늘은 무엇을 배우고 싶으신가요?";
            } else if (currentLanguage === "ar") {
                welcomeMessage = "مرحبا! أنا معلمك الافتراضي. ماذا تريد أن تتعلم اليوم؟";
            } else if (currentLanguage === "es") {
                welcomeMessage = "¡Hola! Soy tu tutor virtual. ¿Qué te gustaría aprender hoy?";
            } else if (currentLanguage === "zh") {
                welcomeMessage = "你好！我是你的虚拟导师。今天你想学习什么？";
            } else if (currentLanguage === "fr") {
                welcomeMessage = "Bonjour ! Je suis votre tuteur virtuel. Que souhaitez-vous apprendre aujourd'hui ?";
            } else if (currentLanguage === "de") {
                welcomeMessage = "Hallo! Ich bin dein virtueller Tutor. Was möchtest du heute lernen?";
            } else if (currentLanguage === "ru") {
                welcomeMessage = "Привет! Я твой виртуальный репетитор. Что ты хочешь изучить сегодня?";
            }

            const welcomeElement = document.createElement("div");
            welcomeElement.classList.add("message", "assistant-message");
            chatContainer.appendChild(welcomeElement);
            streamText(welcomeElement, welcomeMessage);
            
            // Add to conversation history
            conversationHistory = [{
                role: "assistant",
                content: welcomeMessage
            }];
        }

        // Initialize - load history if user is authenticated
        loadConversationHistory();

        // Update language badge
        function updateLanguageBadge() {
            const languageCode = currentLanguage.toUpperCase();
            const levelMap = {
                beginner: "Beginner",
                intermediate: "Intermediate",
                advanced: "Advanced"
            };

            const levelDisplay = levelMap[currentLevel];
            languageBadge.textContent = `${languageCode} - ${levelDisplay}`;

            // Change badge color based on level
            if (currentLevel === "beginner") {
                languageBadge.style.backgroundColor = "var(--success-color)";
            } else if (currentLevel === "intermediate") {
                languageBadge.style.backgroundColor = "var(--warning-color)";
            } else {
                languageBadge.style.backgroundColor = "var(--danger-color)";
            }
        }

        // Fetch topics
        function fetchTopics() {
            fetch(`/virtual-tutor/topics?language=${currentLanguage}&level=${currentLevel}&exercise_type=${currentExercise}`)
                .then(response => response.json())
                .then(data => {
                    topicContainer.innerHTML = "";

                    if (data.length === 0) {
                        topicContainer.innerHTML = '<p class="text-center">No topics available</p>';
                        return;
                    }

                    data.forEach(topic => {
                        const topicElement = document.createElement("div");
                        topicElement.classList.add("topic-card");
                        topicElement.innerHTML = `
                            <div class="py-1 px-1">
                                <small>${topic}</small>
                            </div>
                        `;
                        topicElement.addEventListener("click", () => {
                            userInput.value = topic;
                            sendMessage();
                        });
                        topicContainer.appendChild(topicElement);
                    });
                })
                .catch(error => {
                    console.error("Error fetching topics:", error);
                    topicContainer.innerHTML = '<p class="text-danger">Failed to load topics</p>';
                });
        }

        // Update instructions
        function updateInstructions() {
            let instructions = "";
            let tips = "";

            if (currentExercise === "free_conversation") {
                instructions = `
                    <p><strong>Free Conversation</strong></p>
                    <ul>
                        <li>Start a conversation with the virtual tutor</li>
                        <li>The tutor will adapt to your language level</li>
                        <li>You will receive gentle corrections when making mistakes</li>
                        <li>Choose a topic to start a discussion</li>
                    </ul>
                `;

                tips = `
                    <div class="d-flex gap-2 mb-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> Use the suggested topics to start a more focused conversation.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> If you have difficulty, you can ask the tutor to provide examples or further explanation.
                        </p>
                    </div>
                `;

                chatTitle.textContent = "Free Conversation";
                topicTypeLabel.textContent = "Conversation";
                feedbackContainer.style.display = "none";
            } else if (currentExercise === "writing_exercise") {
                instructions = `
                    <p><strong>Writing Exercise</strong></p>
                    <ul>
                        <li>Choose a topic from the list</li>
                        <li>Write at least 3-5 sentences in the target language</li>
                        <li>Submit your writing to get feedback</li>
                        <li>The tutor will provide corrections and improvement suggestions</li>
                    </ul>
                `;

                tips = `
                    <div class="d-flex gap-2 mb-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> Write at least 3-5 sentences to get better feedback.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> Focus on one aspect you want to improve (e.g., vocabulary, grammar, or sentence structure).
                        </p>
                    </div>
                `;

                chatTitle.textContent = "Writing Exercise";
                topicTypeLabel.textContent = "Writing";
                feedbackContainer.style.display = "none";
            } else if (currentExercise === "speaking_practice") {
                instructions = `
                    <p><strong>Speaking Practice</strong></p>
                    <ul>
                        <li>Choose a topic from the list</li>
                        <li>Click the microphone button to start recording</li>
                        <li>Speak in the target language for 30-60 seconds</li>
                        <li>Click the microphone button again to end recording</li>
                        <li>The tutor will provide feedback on your pronunciation</li>
                    </ul>
                `;

                tips = `
                    <div class="d-flex gap-2 mb-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> Speak clearly and naturally. Don't rush.
                        </p>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> Record in a quiet environment for better results.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <i class="bi bi-lightbulb-fill text-warning fs-5 mt-1"></i>
                        <p class="mb-0">
                            <strong>Tip:</strong> Practice difficult words several times.
                        </p>
                    </div>
                `;

                chatTitle.textContent = "Speaking Practice";
                topicTypeLabel.textContent = "Speaking";
                feedbackContainer.style.display = "none";
            }

            instructionsContainer.innerHTML = instructions;
            tipsContainer.innerHTML = tips;
        }

        // Language select event handler
        languageSelect.addEventListener("change", function () {
            currentLanguage = this.value;
            updateLanguageBadge();
            fetchTopics();

            // Only reset if we're not using a saved conversation
            if (!sessionId || conversationHistory.length <= 1) {
                // Reset conversation history
                conversationHistory = [];

                // Update UI
                chatContainer.innerHTML = "";
                addWelcomeMessage();
            }
        });

        // Level select event handler
        levelSelect.addEventListener("change", function () {
            currentLevel = this.value;
            updateLanguageBadge();
            fetchTopics();
        });

        // Exercise tab event handlers
        exerciseTabs.forEach((tab) => {
            tab.addEventListener("click", function () {
                // Update active tab
                exerciseTabs.forEach((t) => t.classList.remove("active"));
                this.classList.add("active");

                // Update current exercise
                currentExercise = this.dataset.exercise;

                // Update UI
                updateInstructions();
                fetchTopics();

                // Show/hide speaking controls based on exercise type
                if (currentExercise === "speaking_practice") {
                    voiceToggle.style.display = "flex";
                } else {
                    voiceToggle.style.display = "flex"; // Keep it visible but inactive
                    recordingStatus.style.display = "none";
                    if (isRecording) {
                        toggleRecording(); // Stop any ongoing recording
                    }
                }
            });
        });

        // Add message to chat
        function addMessageToChat(role, content) {
            const messageElement = document.createElement("div");
            messageElement.classList.add("message");

            if (role === "user") {
                messageElement.classList.add("user-message");
                messageElement.innerHTML = content.replace(/\n/g, "<br>");
                chatContainer.appendChild(messageElement);
            } else {
                messageElement.classList.add("assistant-message");
                messageElement.innerHTML = ""; // Start empty
                chatContainer.appendChild(messageElement);
                
                // Stream text effect for assistant messages
                streamText(messageElement, content);
            }
            
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Add to conversation history
            conversationHistory.push({
                role: role,
                content: content
            });
        }
        
        // Text streaming effect
        function streamText(element, text) {
            const content = text.replace(/\n/g, "<br>");
            const characters = [...content];
            let currentHTML = "";
            
            // Store animation state
            const animState = {
                startTime: performance.now(),
                lastIndex: 0,
                isDone: false,
                charSpeed: 30,  // milliseconds per character
                pauseAfterPunctuation: 150, // extra pause after punctuation marks
                lastTimestamp: performance.now() // Track last animation timestamp
            };
            
            // Function to handle animation frame
            function animate(timestamp) {
                if (animState.isDone) return;
                
                // Check if we're in background - if yes, still continue but more efficiently
                if (document.hidden) {
                    // Continue animation even in background but with a fixed timedelta
                    const forcedTimeDelta = 16.67; // ~60fps equivalent
                    timestamp = animState.lastTimestamp + forcedTimeDelta;
                }
                
                animState.lastTimestamp = timestamp;
                
                // Calculate how many characters should be shown by now
                const elapsedTime = timestamp - animState.startTime;
                let targetIndex = Math.floor(elapsedTime / animState.charSpeed);
                
                // Process any new characters
                let needsUpdate = false;
                
                while (animState.lastIndex < characters.length && animState.lastIndex <= targetIndex) {
                    // Handle HTML tags for line breaks
                    if (characters[animState.lastIndex] === "<" && 
                        characters.slice(animState.lastIndex, animState.lastIndex + 4).join("") === "<br>") {
                        currentHTML += "<br>";
                        animState.lastIndex += 4;
                    } else {
                        currentHTML += characters[animState.lastIndex];
                        
                        // Add extra delay for punctuation
                        if (['.', '!', '?', ';'].includes(characters[animState.lastIndex])) {
                            targetIndex = Math.max(targetIndex, animState.lastIndex + Math.floor(animState.pauseAfterPunctuation / animState.charSpeed));
                        }
                        
                        animState.lastIndex++;
                    }
                    needsUpdate = true;
                }
                
                // Update the DOM only if needed
                if (needsUpdate) {
                    element.innerHTML = currentHTML;
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
                
                // Check if we're done
                if (animState.lastIndex < characters.length) {
                    requestAnimationFrame(animate);
                } else {
                    animState.isDone = true;
                }
            }
            
            // Start the animation
            requestAnimationFrame(animate);
        }

        // Display writing feedback with animation (for new feedback)
        function displayWritingFeedback(analysis) {
            feedbackContainer.innerHTML = `<h4>Writing Feedback</h4>`;
            
            // Create a container for the feedback content
            const feedbackContent = document.createElement("div");
            feedbackContent.classList.add("feedback-content");
            
            // Stream text effect for the analysis
            streamText(feedbackContent, analysis);
            
            // Add content to feedback container
            feedbackContainer.appendChild(feedbackContent);
            feedbackContainer.style.display = "block";
            
            // Scroll to see the feedback if it's out of view
            setTimeout(() => {
                feedbackContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 300);
        }
        
        // Display saved feedback immediately without animation
        function displaySavedFeedback(analysis) {
            feedbackContainer.innerHTML = `<h4>Writing Feedback</h4>`;
            
            // Create a container for the feedback content and add the analysis directly
            const feedbackContent = document.createElement("div");
            feedbackContent.classList.add("feedback-content");
            feedbackContent.innerHTML = analysis.replace(/\n/g, "<br>");
            
            // Add content to feedback container
            feedbackContainer.appendChild(feedbackContent);
            feedbackContainer.style.display = "block";
        }

        // Send message
        function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            // Add user message to chat
            addMessageToChat("user", message);

            // Clear input
            userInput.value = "";

            // Create response element with a typing indicator
            const responseElement = document.createElement("div");
            responseElement.classList.add("message", "assistant-message");
            
            // Create div for typing indicator that can be replaced
            const typingIndicator = document.createElement("div");
            typingIndicator.classList.add("typing-indicator");
            typingIndicator.innerHTML = '<span></span><span></span><span></span>';
            
            responseElement.appendChild(typingIndicator);
            chatContainer.appendChild(responseElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Send to API
            fetch("/virtual-tutor/chat", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message,
                    language: currentLanguage,
                    level: currentLevel,
                    exercise_type: currentExercise,
                    history: conversationHistory.slice(-10), // Send last 10 messages only
                    session_id: sessionId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Remove typing indicator
                responseElement.removeChild(typingIndicator);
                
                if (data.error) {
                    // Stream error message
                    streamText(responseElement, `Error: ${data.response}`);
                    
                    // Add to conversation history
                    conversationHistory.push({
                        role: "assistant",
                        content: `Error: ${data.response}`
                    });
                    
                    return;
                }

                // Stream text response in the same element
                streamText(responseElement, data.response);
                
                // Add to conversation history
                conversationHistory.push({
                    role: "assistant",
                    content: data.response
                });
                
                // Update session ID if provided
                if (data.session_id) {
                    sessionId = data.session_id;
                }

                // If this is a writing exercise, display analysis
                if (currentExercise === "writing_exercise" && data.analysis) {
                    displayWritingFeedback(data.analysis);
                }
            })
            .catch(error => {
                // Remove typing indicator
                responseElement.removeChild(typingIndicator);
                
                console.error("Error sending message:", error);
                
                // Stream error message
                streamText(responseElement, "Sorry, there was an error processing your message. Please try again.");
                
                // Add to conversation history
                conversationHistory.push({
                    role: "assistant",
                    content: "Sorry, there was an error processing your message. Please try again."
                });
            });
        }

        // Format recording time
        function formatRecordingTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes.toString().padStart(2, "0")}:${remainingSeconds.toString().padStart(2, "0")}`;
        }

        // Toggle recording
        function toggleRecording() {
            if (!isRecording) {
                // Start recording
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];

                        mediaRecorder.addEventListener("dataavailable", event => {
                            audioChunks.push(event.data);
                        });

                        mediaRecorder.addEventListener("stop", () => {
                            const audioBlob = new Blob(audioChunks, { type: "audio/webm" });

                            // Convert to base64
                            const reader = new FileReader();
                            reader.readAsDataURL(audioBlob);
                            reader.onloadend = function () {
                                const base64Audio = reader.result;

                                // Add user message to chat
                                addMessageToChat("user", "[Audio Recording]");

                                // Create response element with a typing indicator
                                const responseElement = document.createElement("div");
                                responseElement.classList.add("message", "assistant-message");
                                
                                // Create div for typing indicator that can be replaced
                                const typingIndicator = document.createElement("div");
                                typingIndicator.classList.add("typing-indicator");
                                typingIndicator.innerHTML = '<span></span><span></span><span></span>';
                                
                                responseElement.appendChild(typingIndicator);
                                chatContainer.appendChild(responseElement);
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                                
                                // Send recording to server
                                fetch("/virtual-tutor/speech", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        audio: base64Audio,
                                        language: currentLanguage,
                                        level: currentLevel
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    // Remove typing indicator
                                    responseElement.removeChild(typingIndicator);

                                    if (data.error) {
                                        // Stream error message
                                        streamText(responseElement, `Error: ${data.feedback}`);
                                        
                                        // Add to conversation history
                                        conversationHistory.push({
                                            role: "assistant",
                                            content: `Error: ${data.feedback}`
                                        });
                                        
                                        return;
                                    }

                                    // Display feedback
                                    feedbackContainer.innerHTML = "";
                                    streamText(feedbackContainer, data.feedback);
                                    feedbackContainer.style.display = "block";

                                    // Stream response message
                                    let responseContent = "";
                                    if (data.transcription) {
                                        responseContent = `I heard: "${data.transcription}"\n\n${data.feedback}`;
                                    } else {
                                        responseContent = data.feedback;
                                    }
                                    
                                    // Stream the response
                                    streamText(responseElement, responseContent);
                                    
                                    // Add to conversation history
                                    conversationHistory.push({
                                        role: "assistant",
                                        content: responseContent
                                    });
                                })
                                .catch(error => {
                                    // Remove typing indicator
                                    responseElement.removeChild(typingIndicator);
                                    
                                    console.error("Error processing speech:", error);
                                    
                                    // Stream error message
                                    streamText(responseElement, "Sorry, there was an error processing your recording. Please try again.");
                                    
                                    // Add to conversation history
                                    conversationHistory.push({
                                        role: "assistant",
                                        content: "Sorry, there was an error processing your recording. Please try again."
                                    });
                                });
                            };

                            // Stop all tracks
                            stream.getTracks().forEach(track => track.stop());
                        });

                        mediaRecorder.start();

                        // Update UI
                        isRecording = true;
                        voiceToggle.classList.add("recording");
                        recordingStatus.style.display = "block";
                        recordingSeconds = 0;
                        recordingTime.textContent = formatRecordingTime(recordingSeconds);

                        // Start timer
                        recordingInterval = setInterval(() => {
                            recordingSeconds++;
                            recordingTime.textContent = formatRecordingTime(recordingSeconds);

                            // Auto-stop after 2 minutes
                            if (recordingSeconds >= 120) {
                                toggleRecording();
                            }
                        }, 1000);
                    })
                    .catch(error => {
                        console.error("Error accessing microphone:", error);
                        alert("Could not access the microphone. Make sure your browser supports microphone access and you have granted permission.");
                    });
            } else {
                // Stop recording
                if (mediaRecorder && mediaRecorder.state !== "inactive") {
                    mediaRecorder.stop();
                }

                // Update UI
                isRecording = false;
                voiceToggle.classList.remove("recording");
                recordingStatus.style.display = "none";

                // Stop timer
                clearInterval(recordingInterval);
            }
        }

        // Event handlers
        sendButton.addEventListener("click", sendMessage);

        userInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        resetButton.addEventListener("click", function () {
            if (isAuthenticated && sessionId) {
                // Send reset request to server
                fetch("/virtual-tutor/reset", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        session_id: sessionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update session ID
                        sessionId = data.new_session_id;
                        
                        // Reset conversation history
                        conversationHistory = [];
                        
                        // Reset UI
                        chatContainer.innerHTML = "";
                        feedbackContainer.innerHTML = "";
                        feedbackContainer.style.display = "none";
                        
                        // Add welcome message
                        addWelcomeMessage();
                    }
                })
                .catch(error => {
                    console.error("Error resetting conversation:", error);
                    alert("There was an error resetting the conversation. Please try again.");
                });
            } else {
                // Reset conversation history
                conversationHistory = [];
                sessionId = null;

                // Reset UI
                chatContainer.innerHTML = "";
                feedbackContainer.innerHTML = "";
                feedbackContainer.style.display = "none";

                // Add welcome message
                addWelcomeMessage();
            }
        });

        voiceToggle.addEventListener("click", function () {
            if (currentExercise !== "speaking_practice") {
                alert("Voice recording is only available for Speaking Practice. Please select the Speaking Practice tab first.");
                return;
            }

            toggleRecording();
        });

        // Initialize
        updateLanguageBadge();
        updateInstructions();
        fetchTopics();
    });
    </script>
    @endpush
</x-app-layout>