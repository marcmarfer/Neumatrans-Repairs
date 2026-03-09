<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\BoostQueryService;

class InsightsController extends Controller
{
    protected BoostQueryService $boostQueryService;

    private const ALLOWED_MODELS = ['gpt-5-mini', 'gpt-5.4'];

    public function __construct(BoostQueryService $boostQueryService)
    {
        $this->boostQueryService = $boostQueryService;
    }

    public function index()
    {
        return Inertia::render('Insights/Index');
    }

    public function getQueryResultsOpenAI(Request $request)
    {
        $query = $request->input('query');
        $model = $request->input('model', 'gpt-5-mini');

        if (empty($query)) {
            return response()->json([
                'error' => 'La consulta no puede estar vacía'
            ], 400);
        }

        if (!in_array($model, self::ALLOWED_MODELS, true)) {
            $model = 'gpt-5-mini';
        }

        $result = $this->boostQueryService->processQueryWithBoost(
            $query,
            config('services.openai.api_key'),
            $model
        );

        return response()->json([
            'response' => $result['response'],
            'query' => $result['query'],
            'sql' => $result['executed_sql'] ?? [],
            'tables' => $result['tables'] ?? [],
        ]);
    }
}