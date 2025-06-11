<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VirtualTutorService
{
    protected $aiService;
    protected $supportedLanguages = [
        'id' => [
            'name' => 'Indonesia',
            'system_prompt' => 'Anda adalah tutor bahasa Indonesia yang ramah dan membantu. Bantu pengguna meningkatkan kemampuan berbahasa Indonesia mereka. Jawab dalam bahasa Indonesia.',
            'language_level_names' => [
                'beginner' => 'Pemula',
                'intermediate' => 'Menengah',
                'advanced' => 'Lanjutan'
            ]
        ],
        'en' => [
            'name' => 'English (US)',
            'system_prompt' => 'You are a friendly and helpful English language tutor. Help the user improve their English language skills. Answer in English.',
            'language_level_names' => [
                'beginner' => 'Beginner',
                'intermediate' => 'Intermediate',
                'advanced' => 'Advanced'
            ]
        ],
        'en-GB' => [
            'name' => 'English (UK)',
            'system_prompt' => 'You are a friendly and helpful British English language tutor. Help the user improve their English language skills. Answer in British English.',
            'language_level_names' => [
                'beginner' => 'Beginner',
                'intermediate' => 'Intermediate',
                'advanced' => 'Advanced'
            ]
        ],
        'ja' => [
            'name' => '日本語',
            'system_prompt' => 'あなたは親切な日本語教師です。ユーザーが日本語能力を向上させるのを手伝ってください。日本語で答えてください。',
            'language_level_names' => [
                'beginner' => '初級',
                'intermediate' => '中級',
                'advanced' => '上級'
            ]
        ],
        'ko' => [
            'name' => '한국어',
            'system_prompt' => '당신은 친절한 한국어 교사입니다. 사용자가 한국어 능력을 향상시키는 것을 도와주세요. 한국어로 대답해 주세요.',
            'language_level_names' => [
                'beginner' => '초급',
                'intermediate' => '중급',
                'advanced' => '고급'
            ]
        ],
        'ar' => [
            'name' => 'العربية',
            'system_prompt' => 'أنت معلم لغة عربية ودود ومساعد. ساعد المستخدم على تحسين مهاراته في اللغة العربية. أجب باللغة العربية.',
            'language_level_names' => [
                'beginner' => 'مبتدئ',
                'intermediate' => 'متوسط',
                'advanced' => 'متقدم'
            ]
        ],
        'es' => [
            'name' => 'Español',
            'system_prompt' => 'Eres un tutor de español amigable y servicial. Ayuda al usuario a mejorar sus habilidades en español. Responde en español.',
            'language_level_names' => [
                'beginner' => 'Principiante',
                'intermediate' => 'Intermedio',
                'advanced' => 'Avanzado'
            ]
        ],
        'zh' => [
            'name' => '中文',
            'system_prompt' => '你是一位友善且乐于助人的中文教师。帮助用户提高他们的中文水平。请用中文回答。',
            'language_level_names' => [
                'beginner' => '初级',
                'intermediate' => '中级',
                'advanced' => '高级'
            ]
        ],
        'fr' => [
            'name' => 'Français',
            'system_prompt' => 'Vous êtes un tuteur de français amical et serviable. Aidez l\'utilisateur à améliorer ses compétences en français. Répondez en français.',
            'language_level_names' => [
                'beginner' => 'Débutant',
                'intermediate' => 'Intermédiaire',
                'advanced' => 'Avancé'
            ]
        ],
        'de' => [
            'name' => 'Deutsch',
            'system_prompt' => 'Sie sind ein freundlicher und hilfsbereiter Deutschlehrer. Helfen Sie dem Benutzer, seine Deutschkenntnisse zu verbessern. Antworten Sie auf Deutsch.',
            'language_level_names' => [
                'beginner' => 'Anfänger',
                'intermediate' => 'Mittelstufe',
                'advanced' => 'Fortgeschritten'
            ]
        ],
        'ru' => [
            'name' => 'Русский',
            'system_prompt' => 'Вы дружелюбный и услужливый преподаватель русского языка. Помогите пользователю улучшить знание русского языка. Отвечайте на русском языке.',
            'language_level_names' => [
                'beginner' => 'Начинающий',
                'intermediate' => 'Средний',
                'advanced' => 'Продвинутый'
            ]
        ],
    ];

    public function __construct(OpenRouterService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate chat response
     *
     * @param string $message
     * @param string $language
     * @param string $level
     * @param array $history
     * @param string $exerciseType
     * @return array
     */
    public function generateChatResponse(string $message, string $language = 'en', string $level = 'beginner', array $history = [], string $exerciseType = 'free_conversation')
    {
        if (!isset($this->supportedLanguages[$language])) {
            return [
                'response' => $language === 'id' ? 'Bahasa tidak didukung.' : 'Language not supported.',
                'error' => true
            ];
        }

        $languageConfig = $this->supportedLanguages[$language];
        $systemPrompt = $languageConfig['system_prompt'];
        $levelName = $languageConfig['language_level_names'][$level] ?? 'Beginner';
        
        // Customize system prompt based on exercise type
        $systemPrompt = $this->buildExercisePrompt($systemPrompt, $levelName, $exerciseType, $language);
        
        // Format conversation history
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Add history (limit to last 10 messages for context window)
        foreach (array_slice($history, -10) as $entry) {
            $messages[] = [
                'role' => $entry['role'],
                'content' => $entry['content']
            ];
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        $response = $this->aiService->generateCompletion('', [
            'messages' => $messages,
            'temperature' => 0.7
        ]);
        
        $responseText = $this->aiService->extractText($response);
        
        if (!$responseText) {
            Log::error('Failed to generate virtual tutor response');
            return [
                'response' => $language === 'id' 
                    ? 'Maaf, saya mengalami kesulitan memahami. Bisakah Anda mencoba lagi?' 
                    : 'I apologize, but I am having trouble understanding. Could you please try again?',
                'error' => true
            ];
        }
        
        $result = [
            'response' => $responseText,
            'language' => $language,
            'level' => $level,
            'exercise_type' => $exerciseType,
            'error' => false
        ];
        
        // Add writing analysis for writing exercises
        if ($exerciseType === 'writing_exercise') {
            $analysis = $this->generateWritingAnalysis($message, $language, $level);
            $result['analysis'] = $analysis;
        }
        
        return $result;
    }
    
    /**
     * Generate writing analysis
     * 
     * @param string $text
     * @param string $language
     * @param string $level
     * @return string
     */
    protected function generateWritingAnalysis(string $text, string $language, string $level)
    {
        if (!isset($this->supportedLanguages[$language])) {
            return $language === 'id' ? 'Bahasa tidak didukung.' : 'Language not supported.';
        }
        
        $languageConfig = $this->supportedLanguages[$language];
        $systemPrompt = $languageConfig['system_prompt'];
        $levelName = $languageConfig['language_level_names'][$level] ?? 'Beginner';
        
        $analysisPrompt = '';
        if ($language === 'id') {
            $analysisPrompt = "Analisis teks berikut dari pelajar bahasa Indonesia tingkat {$levelName}:\n\n{$text}\n\nBerikan: 1) Skor (1-10), 2) Kekuatan, 3) Area yang perlu ditingkatkan, 4) Versi yang dikoreksi. Gunakan bahasa Indonesia dalam analisis Anda.";
        } else {
            $analysisPrompt = "Analyze the following text from a {$levelName} level learner:\n\n{$text}\n\nProvide: 1) Score (1-10), 2) Strengths, 3) Areas for improvement, 4) Corrected version";
        }
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $analysisPrompt]
        ];
        
        $response = $this->aiService->generateCompletion('', [
            'messages' => $messages,
            'temperature' => 0.7
        ]);
        
        return $this->aiService->extractText($response) ?? ($language === 'id' ? 'Tidak dapat menganalisis tulisan.' : 'Unable to analyze writing.');
    }
    
    /**
     * Generate speaking feedback
     *
     * @param string $transcription
     * @param string $language
     * @param string $level
     * @return array
     */
    public function generateSpeakingFeedback(string $transcription, string $language = 'en', string $level = 'beginner')
    {
        if (!isset($this->supportedLanguages[$language])) {
            return [
                'feedback' => $language === 'id' ? 'Bahasa tidak didukung.' : 'Language not supported.',
                'error' => true
            ];
        }
        
        $languageConfig = $this->supportedLanguages[$language];
        $systemPrompt = $languageConfig['system_prompt'];
        $levelName = $languageConfig['language_level_names'][$level] ?? 'Beginner';
        
        $prompt = '';
        if ($language === 'id') {
            $prompt = "Sebagai tutor bahasa Indonesia untuk tingkat {$levelName}, analisis transkrip ucapan ini:

Transkrip: \"{$transcription}\"

Berikan:
1. Skor keseluruhan (1-10)
2. Kekuatan dalam berbicara
3. Area yang perlu ditingkatkan
4. Saran koreksi spesifik
5. Contoh frasa atau kalimat yang benar
";
        } else {
            $prompt = "As a {$language} language tutor for {$levelName} level, analyze this speech transcript:

Transcript: \"{$transcription}\"

Provide:
1. Overall score (1-10)
2. Speaking strengths
3. Areas that need improvement
4. Specific correction suggestions
5. Examples of proper phrases or sentences
";
        }
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $response = $this->aiService->generateCompletion('', [
            'messages' => $messages,
            'temperature' => 0.7
        ]);
        
        $feedback = $this->aiService->extractText($response);
        
        if (!$feedback) {
            Log::error('Failed to generate speaking feedback');
            return [
                'feedback' => $language === 'id' 
                    ? 'Maaf, saya mengalami kesulitan menganalisis ucapan Anda. Bisakah Anda mencoba lagi?' 
                    : 'I apologize, but I am having trouble analyzing your speech. Could you please try again?',
                'error' => true
            ];
        }
        
        return [
            'feedback' => $feedback,
            'error' => false
        ];
    }
    
    /**
     * Get supported languages
     *
     * @return array
     */
    public function getSupportedLanguages()
    {
        $languages = [];
        foreach ($this->supportedLanguages as $code => $config) {
            $languages[$code] = $config['name'];
        }
        return $languages;
    }
    
    /**
     * Get conversation topics
     *
     * @param string $language
     * @param string $level
     * @return array
     */
    public function getConversationTopics(string $language = 'en', string $level = 'beginner')
    {
        // These would ideally come from a database
        $topics = [
            'beginner' => [
                'Introductions and greetings',
                'Family and friends',
                'Daily routines',
                'Food and drinks',
                'Hobbies and interests',
                'Weather and seasons',
                'Shopping',
                'Colors and shapes',
                'Time and dates',
                'Animals and pets'
            ],
            'intermediate' => [
                'Travel and transportation',
                'Education and careers',
                'Health and fitness',
                'Entertainment and media',
                'Technology and gadgets',
                'Environment and nature',
                'Cultural differences',
                'Current events',
                'Social media',
                'Sports and activities'
            ],
            'advanced' => [
                'Global issues',
                'Politics and government',
                'Economic systems',
                'Philosophy and ethics',
                'Literature and art',
                'Science and innovation',
                'History and civilization',
                'Psychology and human behavior',
                'Business and entrepreneurship',
                'Legal systems and justice'
            ]
        ];
        
        // Customize topics based on language for more authentic experience
        if ($language === 'id') {
            $topics['beginner'] = [
                'Perkenalan diri',
                'Keluarga',
                'Hobi dan aktivitas',
                'Makanan favorite',
                'Rutinitas harian'
            ];
        }
        
        return $topics[$level] ?? $topics['beginner'];
    }
    
    /**
     * Get writing prompts
     *
     * @param string $language
     * @param string $level
     * @return array
     */
    public function getWritingPrompts(string $language = 'en', string $level = 'beginner')
    {
        if ($language === 'id') {
            $prompts = [
                'beginner' => [
                    'Ceritakan tentang diri Anda dan keluarga Anda.',
                    'Tuliskan aktivitas Anda selama akhir pekan.',
                    'Deskripsikan rumah atau apartemen Anda.',
                    'Apa makanan favorit Anda dan bagaimana cara membuatnya?',
                    'Tuliskan tentang hobi Anda.'
                ],
                'intermediate' => [
                    'Bagaimana teknologi telah mengubah hidup kita?',
                    'Ceritakan pengalaman liburan yang berkesan.',
                    'Apa pendapat Anda tentang sistem pendidikan saat ini?',
                    'Bandingkan kehidupan di desa dan di kota.',
                    'Tuliskan tentang film atau buku favorit Anda.'
                ],
                'advanced' => [
                    'Bagaimana solusi untuk mengatasi masalah lingkungan di Indonesia?',
                    'Analisis dampak media sosial terhadap hubungan interpersonal.',
                    'Diskusikan peran pendidikan dalam mengurangi kesenjangan sosial.',
                    'Tuliskan esai tentang pentingnya pelestarian budaya lokal di era globalisasi.',
                    'Bagaimana teknologi AI dapat mempengaruhi masa depan pekerjaan?'
                ]
            ];
            return $prompts[$level] ?? $prompts['beginner'];
        }
        
        // Default English prompts
        $prompts = [
            'beginner' => [
                'Describe your family',
                'Write about your daily routine',
                'Describe your favorite food',
                'Write about your hobby',
                'Describe your home',
                'Write a postcard to a friend',
                'Describe your favorite season',
                'Write about your weekend',
                'Describe your pet or favorite animal',
                'Write about your favorite place'
            ],
            'intermediate' => [
                'Write a review of a movie you watched recently',
                'Describe a memorable trip',
                'Write about an important event in your life',
                'Describe your ideal job or career',
                'Write a letter of complaint',
                'Describe a problem in your city and suggest solutions',
                'Write about a cultural tradition in your country',
                'Describe a technology that has changed your life',
                'Write about a book you enjoyed reading',
                'Describe a person who has influenced you'
            ],
            'advanced' => [
                'Discuss the impact of social media on society',
                'Analyze the advantages and disadvantages of remote work',
                'Write an essay on climate change and its effects',
                'Discuss the role of education in modern society',
                'Analyze the ethical implications of artificial intelligence',
                'Write an opinion piece on a current political issue',
                'Discuss the importance of cultural diversity',
                'Analyze the future of transportation',
                'Write an essay comparing two economic systems',
                'Discuss the relationship between technology and privacy'
            ]
        ];
        
        return $prompts[$level] ?? $prompts['beginner'];
    }
    
    /**
     * Get speaking topics
     *
     * @param string $language
     * @param string $level
     * @return array
     */
    public function getSpeakingTopics(string $language = 'en', string $level = 'beginner')
    {
        if ($language === 'id') {
            $topics = [
                'beginner' => [
                    'Perkenalan diri dalam 1 menit',
                    'Ceritakan tentang keluarga Anda',
                    'Deskripsikan kamar atau rumah Anda',
                    'Apa yang Anda lakukan di akhir pekan?',
                    'Ceritakan tentang makanan favorit Anda'
                ],
                'intermediate' => [
                    'Ceritakan tentang film yang baru-baru ini Anda tonton',
                    'Bagaimana pendapat Anda tentang media sosial?',
                    'Apa rencana Anda untuk masa depan?',
                    'Ceritakan tentang tempat wisata di kota Anda',
                    'Bagaimana Anda mengatasi stres?'
                ],
                'advanced' => [
                    'Bagaimana pendapat Anda tentang sistem pendidikan di Indonesia?',
                    'Diskusikan isu lingkungan yang paling penting saat ini',
                    'Bagaimana teknologi mengubah cara kita berkomunikasi?',
                    'Berikan pendapat Anda tentang isu politik terkini',
                    'Bagaimana cara mengatasi masalah kemacetan di kota-kota besar?'
                ]
            ];
            return $topics[$level] ?? $topics['beginner'];
        }
        
        // Default English topics
        $topics = [
            'beginner' => [
                'Introduce yourself in 1 minute',
                'Talk about your family',
                'Describe your room or house',
                'What do you do on weekends?',
                'Talk about your favorite food'
            ],
            'intermediate' => [
                'Talk about a movie you watched recently',
                'What\'s your opinion on social media?',
                'What are your plans for the future?',
                'Describe a tourist attraction in your city',
                'How do you deal with stress?'
            ],
            'advanced' => [
                'What\'s your view on the education system in your country?',
                'Discuss the most important environmental issue today',
                'How has technology changed the way we communicate?',
                'Give your opinion on a current political issue',
                'How would you solve traffic problems in big cities?'
            ]
        ];
        
        return $topics[$level] ?? $topics['beginner'];
    }
    
    /**
     * Build exercise prompt
     *
     * @param string $basePrompt
     * @param string $levelName
     * @param string $exerciseType
     * @param string $language
     * @return string
     */
    protected function buildExercisePrompt(string $basePrompt, string $levelName, string $exerciseType, string $language = 'en')
    {
        if ($language === 'id') {
            $basePrompt .= "\n\nAnda sedang membantu siswa di tingkat {$levelName}. ";
            
            switch ($exerciseType) {
                case 'writing_exercise':
                    $basePrompt .= "Anda membantu dengan latihan menulis. Berikan prompt menulis yang sesuai untuk tingkat mereka. Ketika mereka mengirimkan teks tertulis, berikan umpan balik konstruktif yang berfokus pada tata bahasa, kosakata, struktur, dan koherensi. Sarankan perbaikan spesifik sambil tetap memberikan semangat. Sesuaikan koreksi Anda dengan tingkat mereka. Selalu gunakan bahasa Indonesia dalam respons Anda.";
                    break;
                    
                case 'speaking_practice':
                    $basePrompt .= "Anda membantu dengan latihan berbicara. Tanggapi apa yang dikatakan siswa, ajukan pertanyaan lanjutan untuk membuat mereka terus berbicara, dan berikan koreksi lembut untuk masalah pengucapan atau tata bahasa. Gunakan kosakata yang sesuai dengan tingkat mereka. Jika mereka mengunggah rekaman audio, berikan umpan balik tentang cara berbicara mereka. Selalu gunakan bahasa Indonesia dalam respons Anda.";
                    break;
                    
                case 'grammar_focus':
                    $basePrompt .= "Fokus pada membantu siswa dengan tata bahasa. Jelaskan aturan tata bahasa dengan jelas dan sederhana, berikan contoh, dan koreksi kesalahan tata bahasa dalam pesan mereka. Gunakan terminologi yang sesuai dengan tingkat pemahaman mereka. Selalu gunakan bahasa Indonesia dalam respons Anda.";
                    break;
                    
                case 'free_conversation':
                default:
                    $basePrompt .= "Lakukan percakapan alami dengan siswa. Gunakan kosakata dan tata bahasa yang sesuai untuk tingkat mereka. Tanggapi pertanyaan mereka, koreksi kesalahan utama dengan lembut, dan jaga agar percakapan tetap mengalir. Bersikaplah ramah dan mendukung. Selalu gunakan bahasa Indonesia dalam respons Anda.";
            }
        } else {
            $basePrompt .= "\n\nYou are helping a student at {$levelName} level. ";
            
            switch ($exerciseType) {
                case 'writing_exercise':
                    $basePrompt .= "You are helping with a writing exercise. Provide appropriate writing prompts for their level. When they submit written text, provide constructive feedback focusing on grammar, vocabulary, structure, and coherence. Suggest specific improvements while being encouraging. Tailor your corrections to their level.";
                    break;
                    
                case 'speaking_practice':
                    $basePrompt .= "You are helping with speaking practice. Respond to what the student says, ask follow-up questions to keep them talking, and provide gentle corrections for pronunciation or grammar issues. Use vocabulary appropriate for their level. If they upload an audio recording, provide feedback on their speaking.";
                    break;
                    
                case 'grammar_focus':
                    $basePrompt .= "Focus on helping the student with grammar. Explain grammar rules clearly and simply, provide examples, and correct grammar mistakes in their messages. Use terminology appropriate for their level of understanding.";
                    break;
                    
                case 'free_conversation':
                default:
                    $basePrompt .= "Engage in a natural conversation with the student. Use appropriate vocabulary and grammar for their level. Respond to their questions, correct major errors gently, and keep the conversation flowing. Be friendly and supportive.";
            }
        }
        
        return $basePrompt;
    }
} 