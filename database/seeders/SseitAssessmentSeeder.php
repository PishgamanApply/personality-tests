<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class SseitAssessmentSeeder extends Seeder
{
    /**
     * اجرای سیدر برای ثبت و تنظیم آزمون هوش هیجانی شاته (SSEIT / EIS).
     *
     * این سیدر پرسشنامه استاندارد ۳۳ عبارتی Schutte Self-Report Emotional Intelligence Test
     * را به همراه گزینه‌های مقیاس ۵ درجه‌ای لیکرت، نمره‌گذاری معکوس (سوالات ۵، ۲۸ و ۳۳)
     * و ۴ خرده‌مقیاس معتبر روان‌سنجی به صورت مستقل و کاملاً Idempotent ثبت می‌کند.
     */
    public function run(): void
    {
        $assessment = Assessment::updateOrCreate(
            ['slug' => 'sseit'],
            [
                'name' => 'آزمون هوش هیجانی شاته (SSEIT)',
                'description' => 'آزمون سنجش هوش هیجانی خودگزارش‌دهی شاته (SSEIT / EIS) بر اساس مدل سالووی و مایر، ارزیابی‌کننده ابعاد ادراک، مدیریت هیجانات خود و دیگران و بهره‌برداری از هیجان.',
                'is_active' => true,
            ]
        );

        $version = $assessment->versions()->updateOrCreate(
            ['version' => '1.0'],
            [
                'scoring_config' => [
                    'source' => 'Schutte Self-Report Emotional Intelligence Test (SSEIT / EIS)',
                    'original_reference' => 'Schutte, N. S., Malouff, J. M., Hall, L. E., Haggerty, D. J., Cooper, J. T., Golden, C. J., & Dornheim, L. (1998)',
                    'scale_type' => '5-point Likert',
                    'questions_count' => 33,
                    'reverse_scored_items' => [5, 28, 33],
                    'subscales' => [
                        'perception' => 'ادراک و ارزیابی هیجان (10 سوال)',
                        'managing_self' => 'مدیریت و تنظیم هیجانات خود (9 سوال)',
                        'managing_others' => 'مدیریت هیجانات دیگران (8 سوال)',
                        'utilization' => 'بهره‌برداری از هیجان در حل مسئله (6 سوال)',
                    ],
                    'min_score' => 33,
                    'max_score' => 165,
                ],
                'time_limit_minutes' => 20,
                'published_at' => '2026-11-09 12:00:00',
            ]
        );

        $questionsData = $this->getQuestionsData();

        foreach ($questionsData as $index => $q) {
            $question = Question::updateOrCreate(
                [
                    'assessment_version_id' => $version->id,
                    'question_key' => $q['key'],
                ],
                [
                    'prompt' => $q['prompt_fa'],
                    'type' => 'single_choice',
                    'sort_order' => $index + 1,
                    'is_required' => true,
                    'metadata' => [
                        'item_number' => $q['item_number'],
                        'subscale' => $q['subscale'],
                        'is_reversed' => $q['is_reversed'],
                        'source_prompt_en' => $q['prompt_en'],
                    ],
                ]
            );

            $this->seedOptionsForQuestion(
                question: $question,
                subscale: $q['subscale'],
                isReversed: $q['is_reversed']
            );
        }
    }

    /**
     * ثبت یا به‌روزرسانی ۵ گزینه مقیاس لیکرت برای هر سؤال با لحاظ نمره‌گذاری معکوس در صورت نیاز.
     */
    protected function seedOptionsForQuestion(
        Question $question,
        string $subscale,
        bool $isReversed
    ): void {
        $optionsConfig = [
            [
                'key' => 'likert_1',
                'label' => 'کاملاً مخالفم',
                'raw' => 1,
                'sort_order' => 1,
            ],
            [
                'key' => 'likert_2',
                'label' => 'مخالفم',
                'raw' => 2,
                'sort_order' => 2,
            ],
            [
                'key' => 'likert_3',
                'label' => 'نظری ندارم',
                'raw' => 3,
                'sort_order' => 3,
            ],
            [
                'key' => 'likert_4',
                'label' => 'موافقم',
                'raw' => 4,
                'sort_order' => 4,
            ],
            [
                'key' => 'likert_5',
                'label' => 'کاملاً موافقم',
                'raw' => 5,
                'sort_order' => 5,
            ],
        ];

        foreach ($optionsConfig as $opt) {
            $points = $isReversed ? (6 - $opt['raw']) : $opt['raw'];

            $scoreValues = [
                'raw' => $opt['raw'],
                'points' => $points,
                'subscale' => $subscale,
                $subscale => $points,
            ];

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
     * کلیه ۳۳ عبارت استاندارد پرسشنامه هوش هیجانی شاته تفکیک‌شده بر مبنای ۴ خرده‌مقیاس.
     *
     * @return array<int, array{key: string, item_number: int, subscale: string, is_reversed: bool, prompt_fa: string, prompt_en: string}>
     */
    protected function getQuestionsData(): array
    {
        return [
            [
                'key' => 'sseit_01',
                'item_number' => 1,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'من می‌دانم چه موقع درباره مشکلات شخصی‌ام با دیگران صحبت کنم.',
                'prompt_en' => 'I know when to speak about my personal problems to others.',
            ],
            [
                'key' => 'sseit_02',
                'item_number' => 2,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی با موانعی مواجه می‌شوم، زمان‌هایی را به یاد می‌آورم که با موانع مشابهی روبه‌رو شده و بر آن‌ها غلبه کرده‌ام.',
                'prompt_en' => 'When I am faced with obstacles, I remember times I faced similar obstacles and overcame them.',
            ],
            [
                'key' => 'sseit_03',
                'item_number' => 3,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'انتظار دارم در بیشتر کارهایی که تلاش می‌کنم، موفق شوم.',
                'prompt_en' => 'I expect that I will do well on most things I try.',
            ],
            [
                'key' => 'sseit_04',
                'item_number' => 4,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'دیگران به راحتی با من درددل کرده و به من اعتماد می‌کنند.',
                'prompt_en' => 'Other people find it easy to confide in me.',
            ],
            [
                'key' => 'sseit_05',
                'item_number' => 5,
                'subscale' => 'perception',
                'is_reversed' => true,
                'prompt_fa' => 'فهمیدن پیام‌های غیرکلامی (زبان بدن) دیگران برایم دشوار است.',
                'prompt_en' => 'I find it hard to understand the non-verbal messages of other people.',
            ],
            [
                'key' => 'sseit_06',
                'item_number' => 6,
                'subscale' => 'utilization',
                'is_reversed' => false,
                'prompt_fa' => 'برخی از رویدادهای مهم زندگی‌ام باعث شده‌اند در مورد آنچه مهم است و آنچه مهم نیست تجدید نظر کنم.',
                'prompt_en' => 'Some of the major events of my life have led me to re-evaluate what is important and not important.',
            ],
            [
                'key' => 'sseit_07',
                'item_number' => 7,
                'subscale' => 'utilization',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی خلق و خویم تغییر می‌کند، امکانات و فرصت‌های جدیدی را می‌بینم.',
                'prompt_en' => 'When my mood changes, I see new possibilities.',
            ],
            [
                'key' => 'sseit_08',
                'item_number' => 8,
                'subscale' => 'utilization',
                'is_reversed' => false,
                'prompt_fa' => 'هیجانات و احساسات یکی از چیزهایی هستند که زندگی‌ام را ارزشمند و معنادار می‌کنند.',
                'prompt_en' => 'Emotions are one of the things that make my life worth living.',
            ],
            [
                'key' => 'sseit_09',
                'item_number' => 9,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'هنگامی که احساساتی را تجربه می‌کنم، نسبت به آن‌ها آگاه و هوشیار هستم.',
                'prompt_en' => 'I am aware of my emotions as I experience them.',
            ],
            [
                'key' => 'sseit_10',
                'item_number' => 10,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'انتظار دارم اتفاقات خوب و مثبتی رخ دهد.',
                'prompt_en' => 'I expect good things to happen.',
            ],
            [
                'key' => 'sseit_11',
                'item_number' => 11,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'دوست دارم احساسات و عواطف خود را با دیگران در میان بگذارم.',
                'prompt_en' => 'I like to share my emotions with others.',
            ],
            [
                'key' => 'sseit_12',
                'item_number' => 12,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی احساس مثبتی را تجربه می‌کنم، می‌دانم چگونه آن را تداوم ببخشم و حفظ کنم.',
                'prompt_en' => 'When I experience a positive emotion, I know how to make it last.',
            ],
            [
                'key' => 'sseit_13',
                'item_number' => 13,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'برنامه‌ها و رویدادهایی ترتیب می‌دهم که دیگران از آن لذت ببرند.',
                'prompt_en' => 'I arrange events others enjoy.',
            ],
            [
                'key' => 'sseit_14',
                'item_number' => 14,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'به دنبال فعالیت‌هایی می‌روم که مرا شاد و خوشحال می‌کنند.',
                'prompt_en' => 'I seek out activities that make me happy.',
            ],
            [
                'key' => 'sseit_15',
                'item_number' => 15,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'نسبت به پیام‌های غیرکلامی که به دیگران ارسال می‌کنم آگاه هستم.',
                'prompt_en' => 'I am aware of the non-verbal messages I send to others.',
            ],
            [
                'key' => 'sseit_16',
                'item_number' => 16,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'خودم را طوری نشان می‌دهم که تأثیر خوبی بر دیگران بگذارم.',
                'prompt_en' => 'I present myself in a way that makes a good impression on others.',
            ],
            [
                'key' => 'sseit_17',
                'item_number' => 17,
                'subscale' => 'utilization',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی روحیه مثبتی دارم، حل کردن مسائل برایم آسان‌تر است.',
                'prompt_en' => 'When I am in a positive mood, solving problems is easy for me.',
            ],
            [
                'key' => 'sseit_18',
                'item_number' => 18,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'با نگاه کردن به حالات چهره افراد، احساساتی را که تجربه می‌کنند تشخیص می‌دهم.',
                'prompt_en' => 'By looking at their facial expressions, I recognize the emotions people are experiencing.',
            ],
            [
                'key' => 'sseit_19',
                'item_number' => 19,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'می‌دانم چرا هیجانات و احساساتم دستخوش تغییر می‌شوند.',
                'prompt_en' => 'I know why my emotions change.',
            ],
            [
                'key' => 'sseit_20',
                'item_number' => 20,
                'subscale' => 'utilization',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی روحیه مثبتی دارم، ایده‌های جدید و خلاقانه‌ای به ذهنم می‌رسد.',
                'prompt_en' => 'When I am in a positive mood, I am able to come up with new ideas.',
            ],
            [
                'key' => 'sseit_21',
                'item_number' => 21,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'بر هیجانات و احساسات خود کنترل و تسلط دارم.',
                'prompt_en' => 'I have control over my emotions.',
            ],
            [
                'key' => 'sseit_22',
                'item_number' => 22,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'هنگام تجربه احساسات، به راحتی آن‌ها را در خودم شناسایی می‌کنم.',
                'prompt_en' => 'I easily recognize my emotions as I experience them.',
            ],
            [
                'key' => 'sseit_23',
                'item_number' => 23,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'با تجسم نتیجه خوب برای کارهایی که بر عهده می‌گیرم، به خودم انگیزه می‌دهم.',
                'prompt_en' => 'I motivate myself by imagining a good outcome to tasks I take on.',
            ],
            [
                'key' => 'sseit_24',
                'item_number' => 24,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی دیگران کاری را به خوبی انجام می‌دهند، از آن‌ها تعریف و تمجید می‌کنم.',
                'prompt_en' => 'I compliment others when they have done something well.',
            ],
            [
                'key' => 'sseit_25',
                'item_number' => 25,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'نسبت به پیام‌های غیرکلامی (اشارات و حالات) که دیگران ارسال می‌کنند، آگاهم.',
                'prompt_en' => 'I am aware of the non-verbal messages other people send.',
            ],
            [
                'key' => 'sseit_26',
                'item_number' => 26,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی فردی از رویدادی مهم در زندگی‌اش با من صحبت می‌کند، گویی خود من آن رویداد را تجربه کرده‌ام.',
                'prompt_en' => 'When another person tells me about an important event in his or her life, I almost feel as though I have experienced this event myself.',
            ],
            [
                'key' => 'sseit_27',
                'item_number' => 27,
                'subscale' => 'utilization',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی متوجه تغییر در احساساتم می‌شوم، معمولاً ایده‌های جدیدی به ذهنم خطور می‌کند.',
                'prompt_en' => 'When I feel a change in emotions, I tend to come up with new ideas.',
            ],
            [
                'key' => 'sseit_28',
                'item_number' => 28,
                'subscale' => 'managing_self',
                'is_reversed' => true,
                'prompt_fa' => 'وقتی با چالشی روبه‌رو می‌شوم، به این دلیل که فکر می‌کنم شکست می‌خورم تسلیم می‌شوم.',
                'prompt_en' => 'When I am faced with a challenge, I give up because I believe I will fail.',
            ],
            [
                'key' => 'sseit_29',
                'item_number' => 29,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'فقط با نگاه کردن به دیگران، متوجه می‌شوم چه احساسی دارند.',
                'prompt_en' => 'I know what other people are feeling just by looking at them.',
            ],
            [
                'key' => 'sseit_30',
                'item_number' => 30,
                'subscale' => 'managing_others',
                'is_reversed' => false,
                'prompt_fa' => 'وقتی دیگران ناراحت یا بی‌حوصله هستند، به آن‌ها کمک می‌کنم تا حالشان بهتر شود.',
                'prompt_en' => 'I help other people feel better when they are down.',
            ],
            [
                'key' => 'sseit_31',
                'item_number' => 31,
                'subscale' => 'managing_self',
                'is_reversed' => false,
                'prompt_fa' => 'از روحیات مثبت خود برای تداوم تلاش در مواجهه با موانع کمک می‌گیرم.',
                'prompt_en' => 'I use good moods to help myself keep trying in the face of obstacles.',
            ],
            [
                'key' => 'sseit_32',
                'item_number' => 32,
                'subscale' => 'perception',
                'is_reversed' => false,
                'prompt_fa' => 'با گوش دادن به لحن صدای افراد، می‌توانم بفهمم چه احساسی دارند.',
                'prompt_en' => 'I can tell how people are feeling by listening to the tone of their voice.',
            ],
            [
                'key' => 'sseit_33',
                'item_number' => 33,
                'subscale' => 'perception',
                'is_reversed' => true,
                'prompt_fa' => 'درک اینکه چرا دیگران چنین احساساتی دارند برایم دشوار است.',
                'prompt_en' => 'It is difficult for me to understand why people feel the way they do.',
            ],
        ];
    }
}
