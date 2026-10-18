<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class DiscAssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * این سیدر پرسشنامه ODAT 1.0 (Open-Source Psychometrics Project) را با ۱۶ عبارت و مقیاس پنج‌درجه‌ای لیکرت
     * به صورت کاملاً مستقل و Idempotent در دیتابیس ثبت و به‌روزرسانی می‌کند.
     */
    public function run(): void
    {
        $assessment = Assessment::firstOrCreate(
            ['slug' => 'disc'],
            [
                'name' => 'DISC',
                'description' => 'آزمون شخصیت‌شناسی DISC بر اساس مدل چهاربعدی (تسلط، تأثیرگذاری، ثبات و وظیفه‌شناسی) برگرفته از پروژه روان‌سنجی متن‌باز (ODAT).',
                'is_active' => true,
            ]
        );

        if (! $assessment->is_active) {
            $assessment->update(['is_active' => true]);
        }

        $version = $assessment->versions()->updateOrCreate(
            ['version' => '1.0'],
            [
                'scoring_config' => [
                    'source' => 'Open Source Psychometrics Project (ODAT)',
                    'source_url' => 'https://openpsychometrics.org/tests/ODAT/',
                    'development_url' => 'https://openpsychometrics.org/tests/ODAT/development/',
                    'license' => 'Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)',
                    'scale_type' => '5-point Likert',
                    'dimensions' => ['D', 'I', 'S', 'C'],
                    'questions_count' => 16,
                    'points_per_dimension' => [
                        'min' => 4,
                        'max' => 20,
                    ],
                ],
                'time_limit_minutes' => 15,
                'published_at' => '2026-10-18 12:00:00',
            ]
        );

        $questionsData = $this->getQuestionsData();

        foreach ($questionsData as $index => $qData) {
            $question = Question::updateOrCreate(
                [
                    'assessment_version_id' => $version->id,
                    'question_key' => $qData['key'],
                ],
                [
                    'prompt' => $qData['prompt_fa'],
                    'type' => 'single_choice',
                    'sort_order' => $index + 1,
                    'is_required' => true,
                    'metadata' => [
                        'dimension' => $qData['dimension'],
                        'source_prompt_en' => $qData['prompt_en'],
                        'source' => 'ODAT 1.0',
                    ],
                ]
            );

            $this->seedOptionsForQuestion($question, $qData['dimension']);
        }
    }

    /**
     * ثبت یا به‌روزرسانی ۵ گزینه مقیاس لیکرت برای هر سؤال.
     */
    protected function seedOptionsForQuestion(Question $question, string $dimension): void
    {
        $optionsConfig = [
            [
                'key' => 'likert_1',
                'label' => 'کاملاً مخالفم',
                'value' => 1,
                'sort_order' => 1,
            ],
            [
                'key' => 'likert_2',
                'label' => 'مخالفم',
                'value' => 2,
                'sort_order' => 2,
            ],
            [
                'key' => 'likert_3',
                'label' => 'نظری ندارم',
                'value' => 3,
                'sort_order' => 3,
            ],
            [
                'key' => 'likert_4',
                'label' => 'موافقم',
                'value' => 4,
                'sort_order' => 4,
            ],
            [
                'key' => 'likert_5',
                'label' => 'کاملاً موافقم',
                'value' => 5,
                'sort_order' => 5,
            ],
        ];

        foreach ($optionsConfig as $opt) {
            $scoreValues = [
                'D' => 0,
                'I' => 0,
                'S' => 0,
                'C' => 0,
            ];

            $scoreValues[$dimension] = $opt['value'];

            QuestionOption::updateOrCreate(
                [
                    'question_id' => $question->id,
                    'option_key' => $opt['key'],
                ],
                [
                    'label' => $opt['label'],
                    'score_values' => $scoreValues,
                    'sort_order' => $opt['sort_order'],
                ]
            );
        }
    }

    /**
     * ۱۶ عبارت آزمون ODAT 1.0 تفکیک‌شده بر اساس ۴ بعد شخصیتی.
     *
     * @return array<int, array{key: string, dimension: string, prompt_fa: string, prompt_en: string}>
     */
    protected function getQuestionsData(): array
    {
        return [
            // بُعد D (تسلط - Dominance)
            [
                'key' => 'disc_odat_01',
                'dimension' => 'D',
                'prompt_fa' => 'دیگران را تحت فشار قرار می‌دهم.',
                'prompt_en' => 'I put people under pressure.',
            ],
            [
                'key' => 'disc_odat_02',
                'dimension' => 'D',
                'prompt_fa' => 'نیاز شدیدی به قدرت دارم.',
                'prompt_en' => 'I have a strong need for power.',
            ],
            [
                'key' => 'disc_odat_03',
                'dimension' => 'D',
                'prompt_fa' => 'تلاش می‌کنم از دیگران برتر باشم و بر آنها پیشی بگیرم.',
                'prompt_en' => 'I try to outdo others.',
            ],
            [
                'key' => 'disc_odat_04',
                'dimension' => 'D',
                'prompt_fa' => 'همیشه به دنبال راه‌هایی برای کسب درآمد و منفعت هستم.',
                'prompt_en' => 'I am always on the look out for ways to make money.',
            ],

            // بُعد I (تأثیرگذاری - Influence)
            [
                'key' => 'disc_odat_05',
                'dimension' => 'I',
                'prompt_fa' => 'از بودن در جمع‌های شلوغ و پر سر و صدا لذت می‌برم.',
                'prompt_en' => 'I enjoy being part of a loud crowd.',
            ],
            [
                'key' => 'disc_odat_06',
                'dimension' => 'I',
                'prompt_fa' => 'دوست دارم افراد غریبه به من علاقه‌مند شوند و مرا بپسندند.',
                'prompt_en' => 'I want strangers to love me.',
            ],
            [
                'key' => 'disc_odat_07',
                'dimension' => 'I',
                'prompt_fa' => 'زیاد شوخی و بذله‌گویی می‌کنم.',
                'prompt_en' => 'I joke around a lot.',
            ],
            [
                'key' => 'disc_odat_08',
                'dimension' => 'I',
                'prompt_fa' => 'سر و صدا و هیجان زیادی ایجاد می‌کنم.',
                'prompt_en' => 'I make lots of noise.',
            ],

            // بُعد S (ثبات - Steadiness)
            [
                'key' => 'disc_odat_09',
                'dimension' => 'S',
                'prompt_fa' => 'در انتقاد کردن از ایده‌ها و نظرات دیگران محتاط و مردد هستم.',
                'prompt_en' => 'I hesitate to criticize other people\'s ideas.',
            ],
            [
                'key' => 'disc_odat_10',
                'dimension' => 'S',
                'prompt_fa' => 'برای همکاری و سازش، بیش از رقابت ارزش قائلم.',
                'prompt_en' => 'I value cooperation over competition.',
            ],
            [
                'key' => 'disc_odat_11',
                'dimension' => 'S',
                'prompt_fa' => 'فقط می‌خواهم همه افراد با یکدیگر برابر باشند.',
                'prompt_en' => 'I just want everyone to be equal.',
            ],
            [
                'key' => 'disc_odat_12',
                'dimension' => 'S',
                'prompt_fa' => 'به‌ندرت از خودم و دستاوردهایم تعریف می‌کنم.',
                'prompt_en' => 'I seldom toot my own horn.',
            ],

            // بُعد C (وظیفه‌شناسی - Conscientiousness)
            [
                'key' => 'disc_odat_13',
                'dimension' => 'C',
                'prompt_fa' => 'از نظر احساسی و عاطفی، تودار و خوددار هستم.',
                'prompt_en' => 'I am emotionally reserved.',
            ],
            [
                'key' => 'disc_odat_14',
                'dimension' => 'C',
                'prompt_fa' => 'نکات ریز، مقررات و جزئیات دقیق را به دقت مطالعه می‌کنم.',
                'prompt_en' => 'I read the fine print.',
            ],
            [
                'key' => 'disc_odat_15',
                'dimension' => 'C',
                'prompt_fa' => 'عاشق نظم، ترتیب، چارچوب و قاعده‌مندی هستم.',
                'prompt_en' => 'I love order and regularity.',
            ],
            [
                'key' => 'disc_odat_16',
                'dimension' => 'C',
                'prompt_fa' => 'اولین واکنش من به هر ایده، دیدن ایرادات و نقص‌های آن است.',
                'prompt_en' => 'My first reaction to an idea is to see its flaws.',
            ],
        ];
    }
}
