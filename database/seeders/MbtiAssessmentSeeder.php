<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class MbtiAssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * این سیدر پرسشنامه استاندارد و متن‌باز ۳۲ سؤالی OEJTS 1.2 (Open Extended Jungian Type Scales)
     * را برای آزمون ۱۶ تیپ شخصیتی MBTI به صورت مستقل و کاملاً Idempotent در دیتابیس ثبت و به‌روزرسانی می‌کند.
     */
    public function run(): void
    {
        $assessment = Assessment::updateOrCreate(
            ['slug' => 'mbti'],
            [
                'name' => 'MBTI',
                'description' => 'آزمون تیپ‌شناسی ۱۶ گانه شخصیت مایرز-بریگز (MBTI) بر مبنای مقیاس‌های استاندارد و متن‌باز یونگی (OEJTS 1.2).',
                'is_active' => true,
            ]
        );

        $version = $assessment->versions()->updateOrCreate(
            ['version' => '1.0'],
            [
                'scoring_config' => [
                    'source' => 'Open Extended Jungian Type Scales (OEJTS 1.2)',
                    'source_url' => 'https://openpsychometrics.org/tests/OEJTS/',
                    'development_url' => 'https://openpsychometrics.org/tests/OEJTS/development/',
                    'license' => 'Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)',
                    'dichotomies' => ['EI', 'SN', 'TF', 'JP'],
                    'questions_count' => 32,
                    'scale_type' => '5-point Bipolar Semantic Differential',
                ],
                'time_limit_minutes' => 20,
                'published_at' => '2026-10-22 12:00:00',
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
                        'dichotomy' => $q['dichotomy'],
                        'left_pole' => $q['left_pole'],
                        'right_pole' => $q['right_pole'],
                        'source_prompt_en' => $q['prompt_en'],
                        'left_desc_fa' => $q['left_desc_fa'],
                        'right_desc_fa' => $q['right_desc_fa'],
                    ],
                ]
            );

            $this->seedOptionsForQuestion($question, $q['left_pole'], $q['right_pole'], $q['left_desc_fa'], $q['right_desc_fa']);
        }
    }

    /**
     * ثبت ۵ گزینه طیف پیوسته بین دو قطب رفتاری برای هر سؤال.
     */
    protected function seedOptionsForQuestion(
        Question $question,
        string $leftPole,
        string $rightPole,
        string $leftDesc,
        string $rightDesc
    ): void {
        $optionsConfig = [
            [
                'key' => 'likert_1',
                'label' => "کاملاً متمایل به «{$leftDesc}»",
                'scores' => [$leftPole => 2, $rightPole => 0],
                'sort_order' => 1,
            ],
            [
                'key' => 'likert_2',
                'label' => "تا حدی متمایل به «{$leftDesc}»",
                'scores' => [$leftPole => 1, $rightPole => 0],
                'sort_order' => 2,
            ],
            [
                'key' => 'likert_3',
                'label' => 'حالت میانه / بینابین (بسته به شرایط)',
                'scores' => [$leftPole => 0, $rightPole => 0],
                'sort_order' => 3,
            ],
            [
                'key' => 'likert_4',
                'label' => "تا حدی متمایل به «{$rightDesc}»",
                'scores' => [$leftPole => 0, $rightPole => 1],
                'sort_order' => 4,
            ],
            [
                'key' => 'likert_5',
                'label' => "کاملاً متمایل به «{$rightDesc}»",
                'scores' => [$leftPole => 0, $rightPole => 2],
                'sort_order' => 5,
            ],
        ];

        foreach ($optionsConfig as $opt) {
            $baseScores = [
                'E' => 0, 'I' => 0,
                'S' => 0, 'N' => 0,
                'T' => 0, 'F' => 0,
                'J' => 0, 'P' => 0,
            ];

            foreach ($opt['scores'] as $pole => $val) {
                $baseScores[$pole] = $val;
            }

            QuestionOption::updateOrCreate(
                [
                    'question_id' => $question->id,
                    'option_key' => $opt['key'],
                ],
                [
                    'label' => $opt['label'],
                    'score_values' => $baseScores,
                    'sort_order' => $opt['sort_order'],
                ]
            );
        }
    }

    /**
     * ۳۲ سؤال استاندارد OEJTS 1.2 با تفکیک دقیق قطب‌های چپ و راست.
     *
     * @return array<int, array{key: string, dichotomy: string, left_pole: string, right_pole: string, prompt_fa: string, left_desc_fa: string, right_desc_fa: string, prompt_en: string}>
     */
    protected function getQuestionsData(): array
    {
        return [
            // Q1: JP
            [
                'key' => 'mbti_oejts_01',
                'dichotomy' => 'JP',
                'left_pole' => 'J',
                'right_pole' => 'P',
                'prompt_fa' => 'در مدیریت وظایف و برنامه‌ریزی‌های روزمره:',
                'left_desc_fa' => 'فهرست و چک‌لیست کارهای روزانه تهیه می‌کنم',
                'right_desc_fa' => 'به حافظه و شرایط جاری تکیه می‌کنم',
                'prompt_en' => 'makes lists vs. relies on memory',
            ],
            // Q2: TF
            [
                'key' => 'mbti_oejts_02',
                'dichotomy' => 'TF',
                'left_pole' => 'T',
                'right_pole' => 'F',
                'prompt_fa' => 'در مواجهه با اخبار، ادعاها و نظریات تازه:',
                'left_desc_fa' => 'رویکردی نقادانه و شکاک دارم تا درستی آن اثبات شود',
                'right_desc_fa' => 'تمایل به اعتماد، پذیرش اولیه و حسن‌نیت دارم',
                'prompt_en' => 'sceptical vs. wants to believe',
            ],
            // Q3: EI
            [
                'key' => 'mbti_oejts_03',
                'dichotomy' => 'EI',
                'left_pole' => 'E',
                'right_pole' => 'I',
                'prompt_fa' => 'در مواجهه با اوقات تنهایی و خلوت:',
                'left_desc_fa' => 'تنهایی طولانی مرا خسته و بی‌حوصله می‌کند',
                'right_desc_fa' => 'حتماً به خلوت و زمان تنهایی برای بازیافت انرژی نیاز دارم',
                'prompt_en' => 'bored by time alone vs. needs time alone',
            ],
            // Q4: SN
            [
                'key' => 'mbti_oejts_04',
                'dichotomy' => 'SN',
                'left_pole' => 'S',
                'right_pole' => 'N',
                'prompt_fa' => 'نگرش کلی نسبت به واقعیت‌ها و شرایط پیرامون:',
                'left_desc_fa' => 'شرایط را همان‌گونه که در واقعیت هست می‌پذیرم',
                'right_desc_fa' => 'همیشه در اندیشه دگرگونی و فراتر رفتن از وضع موجودم',
                'prompt_en' => 'accepts things as they are vs. unsatisfied with the ways things are',
            ],
            // Q5: JP
            [
                'key' => 'mbti_oejts_05',
                'dichotomy' => 'JP',
                'left_pole' => 'J',
                'right_pole' => 'P',
                'prompt_fa' => 'وضعیت نظم فیزیکی در اتاق، میز کار و وسایل شخصی:',
                'left_desc_fa' => 'همیشه محیطی آراسته، منظم و با جای مشخص دارم',
                'right_desc_fa' => 'وسایل را آزادانه و هر جا که راحت‌تر باشد می‌گذارم',
                'prompt_en' => 'keeps a clean room vs. just puts stuff where ever',
            ],
            // Q6: TF
            [
                'key' => 'mbti_oejts_06',
                'dichotomy' => 'TF',
                'left_pole' => 'F',
                'right_pole' => 'T',
                'prompt_fa' => 'دیدگاه درباره تفکر کاملاً ماشینی و بی‌طرف:',
                'left_desc_fa' => 'رفتار خشک و «ربات‌گونه» را امری منفی می‌دانم',
                'right_desc_fa' => 'برای رسیدن به ذهنی کاملاً عقلانی، دقیق و منطقی تلاش می‌کنم',
                'prompt_en' => 'thinks "robotic" is an insult vs. strives to have a mechanical mind',
            ],
            // Q7: EI
            [
                'key' => 'mbti_oejts_07',
                'dichotomy' => 'EI',
                'left_pole' => 'E',
                'right_pole' => 'I',
                'prompt_fa' => 'سطح شور، برون‌گرایی و تحرک در برخوردهای اجتماعی:',
                'left_desc_fa' => 'پرانرژی، سرزنده، پرتحرک و پرشور هستم',
                'right_desc_fa' => 'آرام، متین، خونسرد و کم‌هیاهو هستم',
                'prompt_en' => 'energetic vs. mellow',
            ],
            // Q8: SN
            [
                'key' => 'mbti_oejts_08',
                'dichotomy' => 'SN',
                'left_pole' => 'S',
                'right_pole' => 'N',
                'prompt_fa' => 'ترجیح در نوع ارزیابی‌ها، سنجش‌ها و آزمون‌ها:',
                'left_desc_fa' => 'آزمون‌های تستی با پاسخ‌های مشخص، شفاف و عینی',
                'right_desc_fa' => 'پرسش‌های تشریحی و تحلیلی با امکان استدلال و ابراز دیدگاه',
                'prompt_en' => 'prefer to take multiple choice test vs. prefer essay answers',
            ],
            // Q9: JP
            [
                'key' => 'mbti_oejts_09',
                'dichotomy' => 'JP',
                'left_pole' => 'P',
                'right_pole' => 'J',
                'prompt_fa' => 'سبک ساماندهی فعالیت‌ها و پروژه‌های کاری:',
                'left_desc_fa' => 'خودانگیخته، متغیر و بدون چارچوب ثابت و از پیش‌تعیین‌شده',
                'right_desc_fa' => 'منظم، با اسلوب، چارچوب‌دار و به دقت سازمان‌یافته',
                'prompt_en' => 'chaotic vs. organized',
            ],
            // Q10: TF
            [
                'key' => 'mbti_oejts_10',
                'dichotomy' => 'TF',
                'left_pole' => 'F',
                'right_pole' => 'T',
                'prompt_fa' => 'واکنش عاطفی در برابر نقدها، قضاوت‌ها و تندی‌های دیگران:',
                'left_desc_fa' => 'زودرنج و حساس هستم و دلگیر می‌شوم',
                'right_desc_fa' => 'خونسرد و صبورم و مسائل را شخصی برداشت نمی‌کنم',
                'prompt_en' => 'easily hurt vs. thick-skinned',
            ],
            // Q11: EI
            [
                'key' => 'mbti_oejts_11',
                'dichotomy' => 'EI',
                'left_pole' => 'E',
                'right_pole' => 'I',
                'prompt_fa' => 'محیط ایده‌آل برای بیشترین تمرکز و بازدهی کاری:',
                'left_desc_fa' => 'در کار تیمی، گفتگو و تعامل با دیگران بالاترین کارایی را دارم',
                'right_desc_fa' => 'در کار انفرادی، مستقل و فضای آرام بیشترین بازدهی را دارم',
                'prompt_en' => 'works best in groups vs. works best alone',
            ],
            // Q12: SN
            [
                'key' => 'mbti_oejts_12',
                'dichotomy' => 'SN',
                'left_pole' => 'S',
                'right_pole' => 'N',
                'prompt_fa' => 'جهت‌گیری زمانی و کانون اصلی توجه فکری:',
                'left_desc_fa' => 'تمرکز بر واقعیت‌های ملموس زمان حال و اکنون',
                'right_desc_fa' => 'تمرکز بر چشم‌اندازها، فرداها و افق‌های احتمالی آینده',
                'prompt_en' => 'focused on the present vs. focused on the future',
            ],
            // Q13: JP
            [
                'key' => 'mbti_oejts_13',
                'dichotomy' => 'JP',
                'left_pole' => 'J',
                'right_pole' => 'P',
                'prompt_fa' => 'سبک برنامه‌ریزی برای رویدادها، سفرها و قرارها:',
                'left_desc_fa' => 'از مدت‌ها قبل برنامه‌ریزی و پیش‌بینی دقیق می‌کنم',
                'right_desc_fa' => 'در لحظه آخر و متناسب با شرایط همان موقع تصمیم می‌گیرم',
                'prompt_en' => 'plans far ahead vs. plans at the last minute',
            ],
            // Q14: TF
            [
                'key' => 'mbti_oejts_14',
                'dichotomy' => 'TF',
                'left_pole' => 'T',
                'right_pole' => 'F',
                'prompt_fa' => 'ارزش محوری که ترجیح می‌دهید از دیگران دریافت کنید:',
                'left_desc_fa' => 'احترام عقلانی، به رسمیت شناختن شایستگی و جایگاه من',
                'right_desc_fa' => 'مهر، دوستی قلبی، صمیمیت و همدلی واقعی دیگران',
                'prompt_en' => 'wants people\'s respect vs. wants their love',
            ],
            // Q15: EI
            [
                'key' => 'mbti_oejts_15',
                'dichotomy' => 'EI',
                'left_pole' => 'I',
                'right_pole' => 'E',
                'prompt_fa' => 'تأثیر حضور در مهمانی‌ها، جشن‌ها و اجتماعات شلوغ بر انرژی شما:',
                'left_desc_fa' => 'حضور در این جمع‌ها انرژی روانی مرا تحلیل برده و خسته‌ام می‌کند',
                'right_desc_fa' => 'حضور در شلوغی و جشن‌ها به من شور، هیجان و انرژی مضاعف می‌بخشد',
                'prompt_en' => 'gets worn out by parties vs. gets fired up by parties',
            ],
            // Q16: SN
            [
                'key' => 'mbti_oejts_16',
                'dichotomy' => 'SN',
                'left_pole' => 'S',
                'right_pole' => 'N',
                'prompt_fa' => 'رویکرد نسبت به آداب، رسوم و هنجارهای جاافتاده جمع:',
                'left_desc_fa' => 'با عرف و جمع همرنگ و همراه می‌شوم و چارچوب‌ها را مراعات می‌کنم',
                'right_desc_fa' => 'مستقل عمل می‌کنم و تمایل دارم متفاوت و منحصربه‌فرد باشم',
                'prompt_en' => 'fits in vs. stands out',
            ],
            // Q17: JP
            [
                'key' => 'mbti_oejts_17',
                'dichotomy' => 'JP',
                'left_pole' => 'P',
                'right_pole' => 'J',
                'prompt_fa' => 'رویکرد به تصمیم‌گیری‌های قطعی و تعهدات کاری:',
                'left_desc_fa' => 'ترجیح می‌دهم انتخاب‌ها باز بماند تا در صورت نیاز انعطاف داشته باشم',
                'right_desc_fa' => 'ترجیح می‌دهم سریعاً تکلیف مشخص شده و به تعهدی روشن پایبند شوم',
                'prompt_en' => 'keeps options open vs. commits',
            ],
            // Q18: TF
            [
                'key' => 'mbti_oejts_18',
                'dichotomy' => 'TF',
                'left_pole' => 'T',
                'right_pole' => 'F',
                'prompt_fa' => 'اشتیاق و استعداد شما بیشتر در کدام زمینه بروز پیدا می‌کند:',
                'left_desc_fa' => 'تعمیر، بهینه‌سازی و سر درآوردن از سازوکار اشیاء، سیستم‌ها و ابزارها',
                'right_desc_fa' => 'همدلی، رفع دلخوری‌ها و کمک به بهبود حال روحی و روابط انسان‌ها',
                'prompt_en' => 'wants to be good at fixing things vs. wants to be good at fixing people',
            ],
            // Q19: EI
            [
                'key' => 'mbti_oejts_19',
                'dichotomy' => 'EI',
                'left_pole' => 'E',
                'right_pole' => 'I',
                'prompt_fa' => 'در گفتگوها و تعاملات روزمره با دوستان یا همکاران:',
                'left_desc_fa' => 'بیشتر اهل صحبتم و افکار و دیدگاه‌هایم را آزادانه بیان می‌کنم',
                'right_desc_fa' => 'بیشتر ناظر و شنونده‌ام و سخنان دیگران را به دقت می‌شنوم',
                'prompt_en' => 'talks more vs. listens more',
            ],
            // Q20: SN
            [
                'key' => 'mbti_oejts_20',
                'dichotomy' => 'SN',
                'left_pole' => 'S',
                'right_pole' => 'N',
                'prompt_fa' => 'شیوه شرح دادن و بازگویی یک رویداد یا ماجرا:',
                'left_desc_fa' => 'دقیقاً بر جزئیات واقعی، ترتیب وقایع و آنچه رخ داد تمرکز می‌کنم',
                'right_desc_fa' => 'بر مفهوم، چرایی، برداشت کلی و پیام نهفته رویداد تمرکز می‌کنم',
                'prompt_en' => 'tells what happened vs. tells what it meant',
            ],
            // Q21: JP
            [
                'key' => 'mbti_oejts_21',
                'dichotomy' => 'JP',
                'left_pole' => 'J',
                'right_pole' => 'P',
                'prompt_fa' => 'سرعت اقدام در انجام مسئولیت‌ها و وظایف محوله:',
                'left_desc_fa' => 'کارها را فوراً آغاز کرده و در اولین فرصت به پایان می‌رسانم',
                'right_desc_fa' => 'تمایل به تعویق دارم و تحت فشار مهلت پایانی باانگیزه می‌شوم',
                'prompt_en' => 'gets work done right away vs. procrastinates',
            ],
            // Q22: TF
            [
                'key' => 'mbti_oejts_22',
                'dichotomy' => 'TF',
                'left_pole' => 'F',
                'right_pole' => 'T',
                'prompt_fa' => 'راهنمای اصلی شما در گزینش مسیرهای مهم زندگی:',
                'left_desc_fa' => 'پیروی از ندای دل، حس درونی و عواطف قلبی',
                'right_desc_fa' => 'پیروی از استدلال عقلی، منطق و محاسبات سود و زیان',
                'prompt_en' => 'follows the heart vs. follows the head',
            ],
            // Q23: EI
            [
                'key' => 'mbti_oejts_23',
                'dichotomy' => 'EI',
                'left_pole' => 'I',
                'right_pole' => 'E',
                'prompt_fa' => 'شیوه ایده‌آل برای گذراندن یک روز تعطیل و فراغت:',
                'left_desc_fa' => 'استراحت آرام در خانه، مطالعه یا سرگرمی‌های فردی دلخواه',
                'right_desc_fa' => 'بیرون رفتن از خانه، گردش در شهر و حضور در مکان‌های پررفت‌وآمد',
                'prompt_en' => 'stays at home vs. goes out on the town',
            ],
            // Q24: SN
            [
                'key' => 'mbti_oejts_24',
                'dichotomy' => 'SN',
                'left_pole' => 'N',
                'right_pole' => 'S',
                'prompt_fa' => 'در آغاز یک پروژه کاری جدید، ترجیح اول شما چیست:',
                'left_desc_fa' => 'درک تصویر کلان، چشم‌انداز نهایی و مقصود غایی کار',
                'right_desc_fa' => 'آگاهی از جزئیات دقیق، مراحل فنی و دستورالعمل‌های گام‌به‌گام',
                'prompt_en' => 'wants the big picture vs. wants the details',
            ],
            // Q25: JP
            [
                'key' => 'mbti_oejts_25',
                'dichotomy' => 'JP',
                'left_pole' => 'P',
                'right_pole' => 'J',
                'prompt_fa' => 'سبک مواجهه با شرایط و جلسات غیرمنتظره:',
                'left_desc_fa' => 'متکی بر بداهه‌پردازی، سازگاری و اتخاذ راه‌حل در لحظه',
                'right_desc_fa' => 'متکی بر آمادگی قبلی، چارچوب مشخص و طبق برنامه',
                'prompt_en' => 'improvises vs. prepares',
            ],
            // Q26: TF
            [
                'key' => 'mbti_oejts_26',
                'dichotomy' => 'TF',
                'left_pole' => 'T',
                'right_pole' => 'F',
                'prompt_fa' => 'بنیان اصلی قضاوت و داوری شما در خصوص اختلافات:',
                'left_desc_fa' => 'عدالت بی‌طرفانه، قانون، برابری حقوق و قواعد عینی',
                'right_desc_fa' => 'شفقت، همدلی، درک عواطف و موقعیت ویژه انسانی هر فرد',
                'prompt_en' => 'bases morality on justice vs. bases morality on compassion',
            ],
            // Q27: EI
            [
                'key' => 'mbti_oejts_27',
                'dichotomy' => 'EI',
                'left_pole' => 'I',
                'right_pole' => 'E',
                'prompt_fa' => 'سطح راحتی با ابراز وجود صدادار و پرشور در میان جمع:',
                'left_desc_fa' => 'بلند صحبت کردن یا فریاد کشیدن برایم سخت و معذب‌کننده است',
                'right_desc_fa' => 'با صدای رسا، پرطنين و جلب‌توجه‌کننده حرف زدن برایم بسیار طبیعی است',
                'prompt_en' => 'finds it difficult to yell very loudly vs. yelling comes naturally',
            ],
            // Q28: SN
            [
                'key' => 'mbti_oejts_28',
                'dichotomy' => 'SN',
                'left_pole' => 'N',
                'right_pole' => 'S',
                'prompt_fa' => 'علاقه فکری شما به کدام دسته از مباحث نزدیک‌تر است:',
                'left_desc_fa' => 'مباحث نظری، مفاهیم فلسفی، انتزاعی و تئوری‌های بنیادین',
                'right_desc_fa' => 'مباحث تجربی، ملموس، کاربردی و واقعیت‌های آزموده‌شده عینی',
                'prompt_en' => 'theoretical vs. empirical',
            ],
            // Q29: JP
            [
                'key' => 'mbti_oejts_29',
                'dichotomy' => 'JP',
                'left_pole' => 'J',
                'right_pole' => 'P',
                'prompt_fa' => 'دیدگاه اساسی در تنظیم تعادل زندگی روزمره:',
                'left_desc_fa' => 'اولویت قاطع با کار، انجام وظایف و به نتیجه رساندن تعهدات است',
                'right_desc_fa' => 'اولویت با لذت بردن از لحظات، تفریح و تجربه شادی‌های آزادانه است',
                'prompt_en' => 'works hard vs. plays hard',
            ],
            // Q30: TF
            [
                'key' => 'mbti_oejts_30',
                'dichotomy' => 'TF',
                'left_pole' => 'T',
                'right_pole' => 'F',
                'prompt_fa' => 'برخورد شخصی با هیجانات شدید و ابراز احساسات در کار:',
                'left_desc_fa' => 'با ابراز احساسات شدید معذب می‌شوم و تکیه بر منطق را ترجیح می‌دهم',
                'right_desc_fa' => 'به جریان احساسات و تجارب عاطفی عمیقاً بها می‌دهم و آن را ارزشمند می‌دانم',
                'prompt_en' => 'uncomfortable with emotions vs. values emotions',
            ],
            // Q31: EI
            [
                'key' => 'mbti_oejts_31',
                'dichotomy' => 'EI',
                'left_pole' => 'E',
                'right_pole' => 'I',
                'prompt_fa' => 'احساس نسبت به قرار گرفتن در کانون توجه یا ارائه در برابر جمع:',
                'left_desc_fa' => 'از سخنرانی، اجرا و دیده شدن در برابر جمع لذت می‌برم',
                'right_desc_fa' => 'از سخنرانی در جمع پرهیز دارم و ترجیح می‌دهم در حاشیه باشم',
                'prompt_en' => 'likes to perform in front of other people vs. avoids public speaking',
            ],
            // Q32: SN
            [
                'key' => 'mbti_oejts_32',
                'dichotomy' => 'SN',
                'left_pole' => 'S',
                'right_pole' => 'N',
                'prompt_fa' => 'نوع پرسش‌هایی که بیشترین کنجکاوی را در شما برمی‌انگیزد:',
                'left_desc_fa' => 'پرسش‌های عینی: «چه کسی؟»، «دقیقاً چه چیزی؟» و «در چه زمانی؟»',
                'right_desc_fa' => 'پرسش‌های تحلیلی و ژرف: «چرا؟»، «به چه انگیزه‌ای؟» و «با چه معنایی؟»',
                'prompt_en' => 'likes to know "who?", "what?", "when?" vs. likes to know "why?"',
            ],
        ];
    }
}

