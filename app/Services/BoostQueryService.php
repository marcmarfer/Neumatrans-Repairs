<?php

namespace App\Services;

use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Illuminate\Support\Facades\DB;
use OpenAI;

class BoostQueryService
{
    protected const VALUE_TRANSLATIONS = [
        'generic' => 'Genérico',
        'corrective' => 'Correctivo',
        'reception' => 'Recepción',
        'diagnosing' => 'Diagnóstico',
        'in_repair' => 'En reparación',
        'finished' => 'Finalizado',
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ];

    protected const HEADER_TRANSLATIONS = [
        'id' => 'ID', 'name' => 'Nombre', 'type' => 'Tipo', 'status' => 'Estado',
        'description' => 'Descripción', 'total' => 'Total', 'cost' => 'Coste',
        'price' => 'Precio', 'quantity' => 'Cantidad', 'created_at' => 'Creado',
        'updated_at' => 'Actualizado', 'added_at' => 'Fecha registro',
        'completed_at' => 'Completado', 'brand' => 'Marca', 'model' => 'Modelo',
        'plate' => 'Matrícula', 'phone' => 'Teléfono', 'email' => 'Email',
        'address' => 'Dirección', 'nif' => 'NIF', 'family' => 'Familia',
        'supplier' => 'Proveedor', 'reference' => 'Referencia', 'number' => 'Número',
        'benefit' => 'Beneficio', 'margin' => 'Margen', 'month' => 'Mes',
        'count' => 'Cantidad', 'total_amount' => 'Importe total',
        'total_count' => 'Total', 'avg_amount' => 'Media', 'max_amount' => 'Máximo',
        'min_amount' => 'Mínimo', 'total_repairs' => 'Total reparaciones',
        'completed_repairs' => 'Completadas', 'pending_repairs' => 'Pendientes',
        'total_vehicles' => 'Total vehículos', 'unique_clients' => 'Clientes únicos',
        'total_clients' => 'Total clientes',
    ];

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

    public function processQueryWithBoost(string $userQuery, string $apiKey, string $model = 'gpt-5-mini'): array
    {
        $schema = $this->getDatabaseSchema();

        $executedSql = [];
        $sqlCandidates = $this->generateSqlFromQuestion($userQuery, $schema, $apiKey, $model);
        $queryResults = $this->runSqlCandidates($sqlCandidates, $executedSql);

        if (empty($queryResults)) {
            $dataNeeds = $this->analyzeQueryNeeds($userQuery, $schema);
            $queryResults = $this->fetchRelevantData($dataNeeds, $executedSql);
        }

        $tables = $this->buildTablesFromResults($queryResults);
        $summary = $this->summarizeForAI($queryResults);
        $answerPrompt = $this->buildAnswerPrompt($userQuery, $schema, $summary, $executedSql);

        $client = OpenAI::client($apiKey);
        
        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres Mariano, un asistente analítico para un taller de vehículos. Respondes en español, directo y sin rodeos. Cada consulta es independiente: no ofrezcas seguir la conversación ni propongas otras consultas. NO generes tablas markdown. Para indicar dónde debe mostrarse la tabla de datos, escribe el marcador [TABLA] en una línea aparte. La tabla se renderiza automáticamente. Sé breve: una frase de contexto, luego [TABLA], y opcionalmente un comentario final corto. Nunca inventas datos.'
                ],
                [
                    'role' => 'user',
                    'content' => $answerPrompt
                ],
            ],
        ]);

        return [
            'response' => $response['choices'][0]['message']['content'],
            'query' => $userQuery,
            'executed_sql' => $executedSql,
            'tables' => $tables,
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
- NO uses LIMIT a menos que el usuario pida explícitamente un número concreto (ej. "los 5 mejores", "top 3").
- Evita seleccionar columnas de ID interno (id, *_id) a menos que sean necesarias para JOINs. Prefiere columnas con datos legibles.
- Usa SIEMPRE alias en español para las columnas. Ej: SUM(total) AS total_ventas, COUNT(*) AS cantidad, name AS nombre.
- Devuelve SOLO JSON válido:
{
  "queries": [
    "SELECT ...",
    "SELECT ..."
  ]
}

IMPORTANTE - VALORES EN LA BASE DE DATOS:
Los valores se almacenan en INGLÉS. El usuario pregunta en español, pero el SQL debe usar los valores en inglés.
Traducciones conocidas (español → valor en BD):
  Tipos: genérico → 'generic', correctivo → 'corrective'
  Estados de reparación: recepción → 'reception', diagnóstico → 'diagnosing', en reparación → 'in_repair', finalizado → 'finished'
  Cualquier otro término en español que parezca un valor de filtro, tradúcelo al inglés para el SQL.

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

    protected function buildAnswerPrompt(string $userQuery, array $schema, array $summary, array $executedSql): string
    {
        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $sqlJson = json_encode($executedSql, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $summaryJson = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<EOT
Responde la pregunta del usuario basándote en los resultados SQL.

PREGUNTA DEL USUARIO:
{$userQuery}

SQL EJECUTADO:
{$sqlJson}

RESULTADOS (resumen — el usuario verá la tabla completa con todos los registros):
{$summaryJson}

ESQUEMA (referencia):
{$schemaJson}

FORMATO DE RESPUESTA:
- Escribe una frase breve de contexto o resumen.
- Luego escribe [TABLA] en una línea aparte para insertar la tabla de datos.
- Opcionalmente, añade un comentario final breve si aporta valor.
- NO generes tablas markdown. La tabla se renderiza automáticamente donde pongas [TABLA].

Ejemplo de formato:
Se encontraron **X registros** de tipo genérico.

[TABLA]

El proveedor más frecuente es **Nombre** con Y registros.

REGLAS:
1. Sé conciso. La tabla habla por sí sola, no repitas sus datos en el texto.
2. Si ves "total_registros: N", hay N registros totales. La "muestra" es solo para tu análisis.
3. Responde en español, directo, sin rodeos.
4. NO ofrezcas seguir la conversación ni propongas otras consultas.
5. Usa negritas para datos clave puntuales.
6. Redondea importes a 2 decimales con €.
7. Formatea fechas legibles (ej. "8 jun 2011").
8. No muestres IDs internos ni nombres técnicos de columnas.
9. No inventes datos.
10. No expliques traducciones ni mapeos de valores. Los datos ya se muestran traducidos al usuario.
EOT;
    }

    protected function buildTablesFromResults(array $queryResults): array
    {
        $tables = [];

        foreach ($queryResults as $rows) {
            if (empty($rows) || !is_array($rows)) continue;

            $firstRow = $rows[0] ?? null;
            if (!is_array($firstRow)) continue;

            $rawHeaders = array_keys($firstRow);
            $headers = array_map(
                fn ($h) => self::HEADER_TRANSLATIONS[$h] ?? ucfirst(str_replace('_', ' ', $h)),
                $rawHeaders
            );

            $tableRows = array_map(fn ($row) => array_map(function ($value) {
                if (is_string($value) && isset(self::VALUE_TRANSLATIONS[$value])) {
                    return self::VALUE_TRANSLATIONS[$value];
                }
                return $value ?? '';
            }, array_values($row)), $rows);

            $tables[] = ['headers' => $headers, 'rows' => $tableRows];
        }

        return $tables;
    }

    protected function summarizeForAI(array $queryResults): array
    {
        $summary = [];

        foreach ($queryResults as $key => $rows) {
            if (!is_array($rows)) {
                $summary[$key] = $rows;
                continue;
            }
            $total = count($rows);
            $summary[$key] = $total <= 20
                ? $rows
                : ['total_registros' => $total, 'muestra' => array_slice($rows, 0, 20)];
        }

        return $summary;
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
                   ORDER BY month DESC"
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
            $sql = "SELECT * FROM {$table} ORDER BY id DESC";
            $executedSql[] = $sql;
            
            $result = $queryTool->handle(['query' => $sql]);
            
            if ($result instanceof ToolResult) {
                return $this->decodeToolResult($result);
            }
        } catch (\Exception $e) {
        }

        return [];
    }
}
