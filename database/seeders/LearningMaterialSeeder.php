<?php

namespace Database\Seeders;

use App\Models\LearningMaterial;
use Illuminate\Database\Seeder;

class LearningMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Level 1 materials
        LearningMaterial::create([
            'title' => 'Introduction to Basic Grammar',
            'description' => 'Learn the basics of English grammar including nouns, verbs, and simple sentences.',
            'content' => '<p>Grammar is the system of rules that defines the structure of a language. Understanding basic grammar is essential for effective communication.</p>
                        <h4>Parts of Speech</h4>
                        <ul>
                            <li><strong>Nouns:</strong> Words that represent people, places, things, or ideas.</li>
                            <li><strong>Verbs:</strong> Words that express actions or states of being.</li>
                            <li><strong>Adjectives:</strong> Words that describe nouns.</li>
                            <li><strong>Adverbs:</strong> Words that modify verbs, adjectives, or other adverbs.</li>
                        </ul>',
            'type' => 'text',
            'level' => 1,
            'language' => 'en',
            'metadata' => json_encode([
                'duration' => 15,
                'exercises' => [
                    [
                        'question' => 'Which of the following is a noun?',
                        'type' => 'multiple_choice',
                        'options' => ['Run', 'Beautiful', 'House', 'Quickly'],
                        'correct_answer' => 2
                    ],
                    [
                        'question' => 'Identify the verb in this sentence: "She walks to school every day."',
                        'type' => 'text_input',
                        'correct_answer' => 'walks'
                    ]
                ]
            ]),
            'active' => true,
            'order' => 1
        ]);

        LearningMaterial::create([
            'title' => 'Simple Present Tense',
            'description' => 'Learn how to use the simple present tense in English.',
            'content' => '<p>The simple present tense is used to describe habits, unchanging situations, general truths, and fixed arrangements.</p>
                        <h4>Form</h4>
                        <ul>
                            <li><strong>Positive:</strong> I/You/We/They + verb (base form) | He/She/It + verb + s/es</li>
                            <li><strong>Negative:</strong> I/You/We/They + do not + verb | He/She/It + does not + verb</li>
                            <li><strong>Question:</strong> Do + I/you/we/they + verb | Does + he/she/it + verb</li>
                        </ul>',
            'type' => 'text',
            'level' => 1,
            'language' => 'en',
            'metadata' => json_encode([
                'duration' => 20,
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'exercises' => [
                    [
                        'question' => 'Which sentence uses the simple present tense correctly?',
                        'type' => 'multiple_choice',
                        'options' => [
                            'She are playing piano.',
                            'He walk to school.',
                            'They watches TV every night.',
                            'I eat breakfast every morning.'
                        ],
                        'correct_answer' => 3
                    ],
                    [
                        'question' => 'Complete the sentence: "He ____ (work) at a bank."',
                        'type' => 'text_input',
                        'correct_answer' => 'works'
                    ]
                ]
            ]),
            'active' => true,
            'order' => 2
        ]);

        // Level 2 materials
        LearningMaterial::create([
            'title' => 'Past Tense Basics',
            'description' => 'Learn how to use the simple past tense in English.',
            'content' => '<p>The simple past tense is used to describe actions that were completed in the past.</p>
                        <h4>Regular Verbs</h4>
                        <p>Add -ed to the base form of the verb:</p>
                        <ul>
                            <li>work → worked</li>
                            <li>play → played</li>
                            <li>study → studied</li>
                        </ul>
                        <h4>Irregular Verbs</h4>
                        <p>Many common verbs have irregular past forms:</p>
                        <ul>
                            <li>go → went</li>
                            <li>see → saw</li>
                            <li>eat → ate</li>
                        </ul>',
            'type' => 'text',
            'level' => 2,
            'language' => 'en',
            'metadata' => json_encode([
                'duration' => 25,
                'exercises' => [
                    [
                        'question' => 'What is the past tense of "eat"?',
                        'type' => 'multiple_choice',
                        'options' => ['Eated', 'Ate', 'Eaten', 'Eating'],
                        'correct_answer' => 1
                    ],
                    [
                        'question' => 'Complete the sentence: "Yesterday, she ____ (walk) to the store."',
                        'type' => 'text_input',
                        'correct_answer' => 'walked'
                    ]
                ]
            ]),
            'active' => true,
            'order' => 1
        ]);

        // Level 3 materials
        LearningMaterial::create([
            'title' => 'Present Perfect Tense',
            'description' => 'Learn how to use the present perfect tense in English.',
            'content' => '<p>The present perfect tense is used to describe actions that started in the past and continue to the present, or actions that were completed in the very recent past.</p>
                        <h4>Form</h4>
                        <ul>
                            <li><strong>Positive:</strong> Subject + have/has + past participle</li>
                            <li><strong>Negative:</strong> Subject + have/has + not + past participle</li>
                            <li><strong>Question:</strong> Have/Has + subject + past participle</li>
                        </ul>',
            'type' => 'text',
            'level' => 3,
            'language' => 'en',
            'metadata' => json_encode([
                'duration' => 30,
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'exercises' => [
                    [
                        'question' => 'Which sentence uses the present perfect tense correctly?',
                        'type' => 'multiple_choice',
                        'options' => [
                            'I have finish my homework.',
                            'She has lived here for five years.',
                            'They have went to the store.',
                            'He has eating lunch.'
                        ],
                        'correct_answer' => 1
                    ],
                    [
                        'question' => 'Complete the sentence: "We ____ (not/see) that movie yet."',
                        'type' => 'text_input',
                        'correct_answer' => 'have not seen'
                    ]
                ]
            ]),
            'active' => true,
            'order' => 1
        ]);
    }
} 