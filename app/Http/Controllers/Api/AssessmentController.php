<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\JsonResponse;

class AssessmentController extends Controller
{
    public function index(): JsonResponse
    {
        $assessments = Assessment::query()
            ->where('is_active', true)
            ->whereHas('versions', function ($query) {
                $query->whereNotNull('published_at');
            })
            ->with([
                'versions' => function ($query) {
                    $query->whereNotNull('published_at')
                        ->orderByDesc('published_at');
                },
            ])
            ->get()
            ->map(function (Assessment $assessment) {
                $version = $assessment->versions->first();

                return [
                    'slug' => $assessment->slug,
                    'name' => $assessment->name,
                    'description' => $assessment->description,
                    'version' => $version?->version,
                ];
            })
            ->values();

        return response()->json([
            'data' => $assessments,
        ]);
    }
}