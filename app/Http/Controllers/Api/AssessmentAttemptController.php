<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\StartAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Services\CalculateAssessmentResultService;
use App\Models\AssessmentResult;

class AssessmentAttemptController extends Controller
{
    public function store(
        Request $request,
        Assessment $assessment,
        StartAssessmentService $service
    ): JsonResponse {
        $user = $request->user();

        $attempt = $service->start($assessment, $user);

        $attempt->load('version.questions.options');

        $questions = $attempt->version->questions
            ->sortBy('sort_order')
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'key' => $question->question_key,
                    'prompt' => $question->prompt,
                    'type' => $question->type,
                    'required' => $question->is_required,
                    'options' => $question->options
                        ->sortBy('sort_order')
                        ->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'key' => $option->option_key,
                                'label' => $option->label,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'assessment' => [
                    'slug' => $assessment->slug,
                    'name' => $assessment->name,
                    'version' => $attempt->version->version,
                ],
                'questions' => $questions,
            ],
        ], 201);
    }
    public function storeAnswers(
        Request $request,
        AssessmentAttempt $attempt
    ): JsonResponse {
        // کاربر فقط اجازه ثبت پاسخ‌های نوبت آزمون خودش را دارد.
        abort_unless(
            $attempt->user_id === $request->user()->id,
            404
        );

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => [
                'required',
                'integer',
                'distinct',
            ],
            'answers.*.option_id' => [
                'required',
                'integer',
            ],
        ]);

        $savedCount = DB::transaction(function () use (
            $attempt,
            $validated
        ) {
            $attempt = AssessmentAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'attempt' => 'این نوبت آزمون دیگر باز نیست.',
                ]);
            }

            foreach ($validated['answers'] as $answer) {
                $question = $attempt->version
                    ->questions()
                    ->whereKey($answer['question_id'])
                    ->first();

                if (! $question) {
                    throw ValidationException::withMessages([
                        'answers' => 'یکی از سؤال‌ها متعلق به این نسخه آزمون نیست.',
                    ]);
                }

                $optionExists = $question->options()
                    ->whereKey($answer['option_id'])
                    ->exists();

                if (! $optionExists) {
                    throw ValidationException::withMessages([
                        'answers' => 'یکی از گزینه‌ها متعلق به سؤال انتخاب‌شده نیست.',
                    ]);
                }

                AssessmentAnswer::updateOrCreate(
                    [
                        'assessment_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'selected_option_id' => $answer['option_id'],
                        'answer_value' => null,
                    ]
                );
            }

            return count($validated['answers']);
        });

        return response()->json([
            'message' => 'پاسخ‌ها با موفقیت ذخیره شدند.',
            'saved_count' => $savedCount,
        ]);
    }
    public function complete(
        Request $request,
        AssessmentAttempt $attempt,
        CalculateAssessmentResultService $service
    ): JsonResponse {
        abort_unless(
            $attempt->user_id === $request->user()->id,
            404
        );

        $result = $service->calculate($attempt);

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => 'completed',
                'result' => [
                    'scores' => $result->scores,
                    'primary_type' => $result->primary_type,
                    'calculated_at' => $result->calculated_at,
                ],
            ],
        ]);
    }
    
    public function history(Request $request): JsonResponse
    {
        $attempts = AssessmentAttempt::query()
            ->where('user_id', $request->user()->id)
            ->with(['version.assessment', 'result'])
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data' => $attempts->getCollection()->map(
                function ($attempt) {
                    return [
                        'attempt_id' => $attempt->id,
                        'status' => $attempt->status,
                        'started_at' => $attempt->started_at,
                        'completed_at' => $attempt->completed_at,
                        'assessment' => [
                            'slug' => $attempt->version->assessment->slug,
                            'name' => $attempt->version->assessment->name,
                            'version' => $attempt->version->version,
                        ],
                        'result' => $attempt->result ? [
                            'scores' => $attempt->result->scores,
                            'primary_type' => $attempt->result->primary_type,
                        ] : null,
                    ];
                }
            )->values(),
            'pagination' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'total' => $attempts->total(),
            ],
        ]);
    }

    public function showResult(
        Request $request,
        AssessmentAttempt $attempt
    ): JsonResponse {
        // فقط مالک تلاش اجازه مشاهده آن را دارد.
        abort_unless(
            $attempt->user_id === $request->user()->id,
            404
        );

        $attempt->load('result', 'version.assessment');

        if ($attempt->status !== 'completed' || ! $attempt->result) {
            return response()->json([
                'message' => 'نتیجه این آزمون هنوز آماده نیست.',
            ], 409);
        }

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'assessment' => [
                    'slug' => $attempt->version->assessment->slug,
                    'name' => $attempt->version->assessment->name,
                    'version' => $attempt->version->version,
                ],
                'result' => [
                    'scores' => $attempt->result->scores,
                    'primary_type' => $attempt->result->primary_type,
                    'report' => $attempt->result->report,
                    'calculated_at' => $attempt->result->calculated_at,
                ],
            ],
        ]);
    }

}