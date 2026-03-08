<?php

namespace App\Services;

use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Illuminate\Support\Facades\DB;
use OpenAI;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Enums\ModelVariation;
use Gemini\Enums\ModelType;

class BoostQueryService
{
    /**
     * Convierte un ToolResult JSON de Laravel MCP a array PHP.
     */
    protected function decodeToolResult(ToolResult $result): array
    {
        if ($result->isError || empty($result->content)) {
            return [];
        }

        $firstContent = $result->content[0] ?? null;

        if (! $firstContent || ! property_exists($firstContent, 'text')) {
            return [];
        }

        $decoded = json_decode($firstContent->text, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function processQueryWithBoost(string $userQuery, string $apiKey, string $model = 'gpt-5.4'): array
    {
        $schema = $this->getDatabaseSchema();

        $executedSql = [];
        $sqlCandidates = $this->generateSqlFromQuestion($userQuery, $schema, $apiKey, $model);
        $queryResults = $this->runSqlCandidates($sqlCandidates, $executedSql);

        if (empty($queryResults)) {
            $dataNeeds = $this->analyzeQueryNeeds($userQuery, $schema);
            $queryResults = $this->fetchRelevantData($dataNeeds, $executedSql);
        }

        $answerPrompt = $this->buildAnswerPrompt($userQuery, $schema, $queryResults, $executedSql);

        $client = OpenAI::client($apiKey);
        
        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres Mariano, un asistente analítico para un taller de vehículos. Respondes en español, directo y sin rodeos. Cada consulta es independiente: no ofrezcas seguir la conversación ni propongas otras consultas. Puedes usar formato markdown (negritas, tablas, listas) cuando ayude a la legibilidad. Si la respuesta involucra datos estructurados (rankings, tops, comparativas), usa tablas markdown. Nunca inventas datos.'
                ],
                [
                    'role' => 'user',
                    'content' => $answerPrompt
                ],
            ],
            'temperature' => 0.4,
        ]);

        return [
            'response' => $response['choices'][0]['message']['content'],
            'query' => $userQuery,
            'data_used' => array_keys($queryResults),
            'executed_sql' => $executedSql,
        ];
    }

    protected function generateSqlFromQuestion(string $userQuery, array $schema, string $apiKey, string $model): array
    {
        $client = OpenAI::client($apiKey);
        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $plannerPrompt = <<<EOT
Genera SQL de solo lectura para responder la pregunta del usuario usando el esquema.

Reglas:
- Solo SELECT / WITH / SHOW / DESCRIBE / EXPLAIN.
- Usa columnas reales.
- Si hay año/mes/fecha en la pregunta, filtra por fecha.
- Si se pregunta por inversión por familia, usa SUM(cost) GROUP BY family.
- Añade LIMIT cuando proceda.
- Devuelve SOLO JSON válido:
{
  "queries": [
    "SELECT ...",
    "SELECT ..."
  ]
}

Pregunta:
{$userQuery}

Esquema:
{$schemaJson}
EOT;

        $planResponse = $client->chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'Eres experto en SQL MySQL y análisis de datos.'],
                ['role' => 'user', 'content' => $plannerPrompt],
            ],
            'temperature' => 0.1,
        ]);

        $raw = trim($planResponse['choices'][0]['message']['content'] ?? '');
        $raw = preg_replace('/^```json\s*|\s*```$/i', '', $raw) ?? $raw;
        $decoded = json_decode(trim($raw), true);

        if (! is_array($decoded) || ! isset($decoded['queries']) || ! is_array($decoded['queries'])) {
            return [];
        }

        return collect($decoded['queries'])
            ->filter(fn ($sql) => is_string($sql) && $this->isLikelyReadOnlySql($sql))
            ->map(fn ($sql) => trim($sql))
            ->values()
            ->all();
    }

    protected function isLikelyReadOnlySql(string $sql): bool
    {
        $firstToken = strtoupper(strtok(ltrim($sql), " \t\n\r") ?: '');

        return in_array($firstToken, ['SELECT', 'WITH', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'], true);
    }

    protected function runSqlCandidates(array $sqlCandidates, array &$executedSql): array
    {
        $queryTool = new DatabaseQuery();
        $resultSets = [];

        foreach ($sqlCandidates as $index => $sql) {
            try {
                $executedSql[] = $sql;
                $result = $queryTool->handle(['query' => $sql]);

                if ($result instanceof ToolResult) {
                    $resultSets['query_'.($index + 1)] = $this->decodeToolResult($result);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $resultSets;
    }

    protected function buildAnswerPrompt(string $userQuery, array $schema, array $queryResults, array $executedSql): string
    {
        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $sqlJson = json_encode($executedSql, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resultsJson = json_encode($queryResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<EOT
Responde la pregunta del usuario usando SOLO los resultados de las consultas SQL adjuntas.

PREGUNTA DEL USUARIO:
{$userQuery}

SQL EJECUTADO:
{$sqlJson}

RESULTADOS:
{$resultsJson}

ESQUEMA (referencia):
{$schemaJson}

REGLAS:
1. Responde en español, directo, sin rodeos.
2. NO ofrezcas seguir la conversación, NO propongas otras consultas ni digas "si quieres puedo...". Cada consulta es independiente.
3. Si la respuesta es simple (un dato, una explicación), responde solo con texto natural.
4. Si la respuesta involucra varios registros o datos comparativos (rankings, tops, listados), usa una tabla markdown. Ejemplo:

| Proveedor | Familia | Beneficio | Margen | Fecha |
|-----------|---------|-----------|--------|-------|
| Maragall | Filtros | 335,18 € | 83,91 % | 8 jun 2011 |

5. Usa negritas solo para destacar datos clave puntuales, no en cada palabra.
6. Redondea importes a 2 decimales y usa € como moneda.
7. Formatea fechas de forma corta y legible (ej. "8 jun 2011").
8. No muestres IDs internos ni nombres de columnas SQL.
9. Traduce SIEMPRE los valores internos de la base de datos al español:
   - generic → genérico, corrective → correctivo
   - reception → recepción, diagnosing → diagnóstico, in_repair → en reparación, finished → finalizado
   - Cualquier otro valor técnico en inglés debe traducirse a su equivalente natural en español.
10. Si faltan datos, dilo brevemente.
11. No inventes datos.
EOT;
    }

    public function processQueryWithBoostGemini(string $userQuery): array
    {
        $schema = $this->getDatabaseSchema();
        
        $dataNeeds = $this->analyzeQueryNeeds($userQuery, $schema);
        
        $executedSql = [];
        $relevantData = $this->fetchRelevantData($dataNeeds, $executedSql);
        
        $prompt = $this->buildOptimizedPrompt($userQuery, $schema, $relevantData);
        
        $model = ModelType::generateGeminiModel(ModelVariation::FLASH, 2.0);
        $response = Gemini::generativeModel($model)->generateContent($prompt);

        return [
            'response' => $response->text(),
            'query' => $userQuery,
            'data_used' => array_keys($relevantData),
            'executed_sql' => $executedSql,
        ];
    }

    protected function getDatabaseSchema(): array
    {
        try {
            $schemaTool = new DatabaseSchema();
            $result = $schemaTool->handle([]);
            
            if ($result instanceof ToolResult) {
                return $this->decodeToolResult($result);
            }
        } catch (\Exception $e) {
            return $this->getBasicSchema();
        }
        
        return [];
    }

    protected function getBasicSchema(): array
    {
        $tables = ['repairs', 'vehicles', 'clients', 'delivery_notes', 'repair_orders'];
        $schema = ['tables' => []];

        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
                $schema['tables'][$table] = [
                    'columns' => array_combine($columns, array_map(function($col) use ($table) {
                        return ['type' => \Illuminate\Support\Facades\Schema::getColumnType($table, $col)];
                    }, $columns))
                ];
            }
        }

        return $schema;
    }

    protected function analyzeQueryNeeds(string $query, array $schema): array
    {
        $needs = [
            'tables' => [],
            'time_range' => null,
            'aggregations' => false,
            'specific_fields' => [],
        ];

        $queryLower = strtolower($query);

        $tables = ['repairs', 'vehicles', 'clients', 'delivery_notes', 'repair_orders'];
        foreach ($tables as $table) {
            if (str_contains($queryLower, $table) || str_contains($queryLower, str_replace('_', ' ', $table))) {
                $needs['tables'][] = $table;
            }
        }

        if (empty($needs['tables'])) {
            $needs['tables'] = ['repairs', 'vehicles', 'clients', 'delivery_notes'];
        }

        $timeKeywords = ['mes', 'meses', 'año', 'años', 'semana', 'semanas', 'día', 'días', 'fecha', 'fechas', 'temporal', 'tendencia'];
        foreach ($timeKeywords as $keyword) {
            if (str_contains($queryLower, $keyword)) {
                $needs['time_range'] = true;
                break;
            }
        }

        $aggKeywords = ['total', 'suma', 'promedio', 'media', 'máximo', 'mínimo', 'contar', 'beneficio', 'beneficios', 'ingresos'];
        foreach ($aggKeywords as $keyword) {
            if (str_contains($queryLower, $keyword)) {
                $needs['aggregations'] = true;
                break;
            }
        }

        return $needs;
    }

    protected function fetchRelevantData(array $needs, array &$executedSql = []): array
    {
        $data = [];

        foreach ($needs['tables'] as $table) {
            if ($needs['aggregations'] || $needs['time_range']) {
                $data[$table] = $this->getAggregatedData($table, $needs, $executedSql);
            } else {
                $data[$table] = $this->getLimitedData($table, $executedSql);
            }
        }

        return $data;
    }

    protected function getAggregatedData(string $table, array $needs, array &$executedSql = []): array
    {
        $queryTool = new DatabaseQuery();
        
        try {
            $sql = $this->buildAggregationQuery($table, $needs);
            
            if ($sql) {
                $executedSql[] = $sql;
                $result = $queryTool->handle(['query' => $sql]);
                
                if ($result instanceof ToolResult) {
                    return $this->decodeToolResult($result);
                }
            }
        } catch (\Exception $e) {
            return $this->getLimitedData($table, $executedSql);
        }

        return [];
    }

    protected function buildAggregationQuery(string $table, array $needs): ?string
    {
        return match($table) {
            'delivery_notes' => $needs['time_range'] 
                ? "SELECT 
                    DATE_FORMAT(added_at, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(total) as total_amount
                   FROM delivery_notes 
                   GROUP BY DATE_FORMAT(added_at, '%Y-%m')
                   ORDER BY month DESC
                   LIMIT 24"
                : "SELECT 
                    COUNT(*) as total_count,
                    SUM(total) as total_amount,
                    AVG(total) as avg_amount,
                    MAX(total) as max_amount,
                    MIN(total) as min_amount
                   FROM delivery_notes",
            
            'repairs' => "SELECT 
                COUNT(*) as total_repairs,
                COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_repairs,
                COUNT(CASE WHEN completed_at IS NULL THEN 1 END) as pending_repairs
             FROM repairs",
            
            'vehicles' => "SELECT 
                COUNT(*) as total_vehicles,
                COUNT(DISTINCT client_id) as unique_clients
             FROM vehicles",
            
            'clients' => "SELECT 
                COUNT(*) as total_clients
             FROM clients",
            
            default => null
        };
    }

    protected function getLimitedData(string $table, array &$executedSql = []): array
    {
        $queryTool = new DatabaseQuery();
        
        try {
            $sql = "SELECT * FROM {$table} ORDER BY id DESC LIMIT 50";
            $executedSql[] = $sql;
            
            $result = $queryTool->handle(['query' => $sql]);
            
            if ($result instanceof ToolResult) {
                return $this->decodeToolResult($result);
            }
        } catch (\Exception $e) {
        }

        return [];
    }

    protected function buildOptimizedPrompt(string $userQuery, array $schema, array $relevantData): string
    {
        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $dataJson = json_encode($relevantData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<EOT
Eres un asistente analítico preciso para un taller de reparación de vehículos. Tu tarea es responder con información exacta basada en los datos proporcionados, con un tono directo y conciso.

ESQUEMA DE LA BASE DE DATOS:
{$schemaJson}

DATOS RELEVANTES PARA TU CONSULTA:
{$dataJson}

INSTRUCCIONES IMPORTANTES SOBRE LOS DATOS:
- En las NOTAS DE ENTREGA (albaranes), el campo "added_at" es la fecha de registro del albarán.
- Debes utilizar este campo para análisis temporales (diario, mensual, anual).
- Para análisis por mes, extrae el mes de este campo de fecha.
- Todos los cálculos de tendencias temporales deben basarse en estas fechas de registro.
- Los datos proporcionados son específicos y relevantes para responder la consulta. Si necesitas información adicional, indica qué datos faltan.

INSTRUCCIONES PARA EL ANÁLISIS:
1. Analiza los datos disponibles y responde DIRECTAMENTE a la consulta, sin rodeos ni explicaciones innecesarias.
2. Ve al grano en tus respuestas, proporcionando la información relevante sin estructuras complejas.
3. Si preguntan por información específica (como "qué mes aportó más beneficios"), responde de forma directa:
   Ejemplo: "Julio fue el mes que más beneficios te aportó (8.500€), principalmente gracias al proveedor X."
4. NO organices la información en secciones o categorías a menos que sea absolutamente necesario.
5. Cuando los datos sean insuficientes para responder, sé breve y directo al indicarlo.

INSTRUCCIONES PARA EL ESTILO DE RESPUESTA:
- Usa un tono conversacional y cercano, pero ve directo al punto.
- Proporciona respuestas en párrafos concisos, evitando listas y secciones cuando sea posible.
- Incluye emojis ocasionales para dar calidez, especialmente en saludos y despedidas.
- Destaca lo más importante de tu análisis en una o dos frases.
- Ofrece siempre el dato o conclusión principal al inicio de tu respuesta.
- Evita estructuras complejas o formatos elaborados.
- Termina con una breve despedida y un emoji apropiado.

Consulta del usuario: '{$userQuery}'
EOT;
    }
}
