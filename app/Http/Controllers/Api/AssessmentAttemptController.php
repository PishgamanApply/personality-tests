<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Services\CalculateAssessmentResultService;
use App\Services\StartAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                'started_at' => $attempt->started_at?->toIso8601String(),
                'assessment' => [
                    'slug' => $assessment->slug,
                    'name' => $assessment->name,
                    'version' => $attempt->version->version,
                ],
                'questions' => $questions,
                'time_limit_minutes' => $attempt->version->time_limit_minutes,
                'expires_at' => $attempt->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function storeAnswers(
        Request $request,
        AssessmentAttempt $attempt,
        CalculateAssessmentResultService $service
    ): JsonResponse {
        // کاربر فقط اجازه ثبت پاسخ‌های نوبت آزمون خودش را دارد.
        abort_unless(
            $attempt->user_id === $request->user()->id,
            404
        );

        $attempt->refresh();

        if (
            $attempt->status === 'in_progress' &&
            $attempt->expires_at &&
            now()->greaterThanOrEqualTo($attempt->expires_at)
        ) {
            $service->calculate($attempt, true);

            return response()->json([
                'message' => 'مهلت آزمون به پایان رسیده است.',
                'status' => 'timed_out',
            ], 409);
        }

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

        $timedOut = $attempt->expires_at
            && now()->greaterThanOrEqualTo($attempt->expires_at);

        $result = $service->calculate($attempt, (bool) $timedOut);

        $attempt->refresh();

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'result' => [
                    'scores' => $result->scores,
                    'primary_type' => $result->primary_type,
                    'calculated_at' => $result->calculated_at
                        ?->timezone(config('app.timezone'))
                        ->toIso8601String(),
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
                        'completed_at' => $attempt->completed_at
                            ?->timezone(config('app.timezone'))
                            ->toIso8601String(),
                        'started_at' => $attempt->started_at
                            ?->timezone(config('app.timezone'))
                            ->toIso8601String(),
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
        AssessmentAttempt $attempt,
        CalculateAssessmentResultService $service
    ): JsonResponse {
        // فقط مالک تلاش اجازه مشاهده آن را دارد.
        abort_unless(
            $attempt->user_id === $request->user()->id,
            404
        );

        // در صورتی که مهلت آزمون گذشته باشد ولی هنوز وضعیت آن محاسبه نشده باشد، خودکار محاسبه می‌شود
        if (
            $attempt->status === 'in_progress' &&
            $attempt->expires_at &&
            now()->greaterThanOrEqualTo($attempt->expires_at)
        ) {
            $service->calculate($attempt, true);
            $attempt->refresh();
        }

        $attempt->load('result', 'version.assessment');

        if (
            ! in_array($attempt->status, ['completed', 'timed_out'], true)
            || ! $attempt->result
        ) {
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
                    'calculated_at' => $attempt->result->calculated_at
                        ?->timezone(config('app.timezone'))
                        ->toIso8601String(),
                ],
            ],
        ]);
    }
}
