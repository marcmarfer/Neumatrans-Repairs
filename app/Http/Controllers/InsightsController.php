<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\DeliveryNote;
use App\Services\BoostQueryService;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Enums\ModelVariation;
use Gemini\Enums\ModelType;
use OpenAI;

class InsightsController extends Controller
{
    protected BoostQueryService $boostQueryService;

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

        if (empty($query)) {
            return response()->json([
                'error' => 'La consulta no puede estar vacía'
            ], 400);
        }

        try {
            $result = $this->boostQueryService->processQueryWithBoost(
                $query,
                env('OPENAI_API_KEY'),
                'gpt-5.4'
            );

            return response()->json([
                'response' => $result['response'],
                'query' => $result['query'],
                'sql' => $result['executed_sql'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return $this->getQueryResultsOpenAIFallback($request);
        }
    }

    protected function getQueryResultsOpenAIFallback(Request $request)
    {
        $repairs = Repair::all();
        $vehicles = Vehicle::all();
        $clients = Client::all();
        $deliveryNotes = DeliveryNote::all();

        $query = $request->input('query');

        $repairsData = json_encode($repairs->toArray());
        $vehiclesData = json_encode($vehicles->toArray());
        $clientsData = json_encode($clients->toArray());
        $deliveryNotesData = json_encode($deliveryNotes->toArray());

        $prompt = <<<EOT
        Tienes acceso a estos datos reales del taller:
            - REPARACIONES: $repairsData
            - VEHÍCULOS: $vehiclesData
            - CLIENTES: $clientsData
            - NOTAS DE ENTREGA (albaranes): $deliveryNotesData

        SOBRE LOS DATOS:
            - El campo "added_at" en albaranes es la fecha de registro. Úsalo para análisis temporales.

        REGLAS:
            1. Responde en español, directo, sin rodeos.
            2. NO ofrezcas seguir la conversación, NO propongas otras consultas ni digas "si quieres puedo...". Cada consulta es independiente.
            3. Si la respuesta es simple (un dato, una explicación), responde solo con texto natural.
            4. Si la respuesta involucra varios registros o datos comparativos, usa una tabla markdown.
            5. Usa negritas solo para destacar datos clave puntuales, no en cada palabra.
            6. Redondea importes a 2 decimales y usa €.
            7. Formatea fechas de forma corta y legible (ej. "8 jun 2011").
            8. No muestres IDs internos ni nombres de columnas SQL.
            9. Traduce SIEMPRE los valores internos de la base de datos al español:
               - generic → genérico, corrective → correctivo
               - reception → recepción, diagnosing → diagnóstico, in_repair → en reparación, finished → finalizado
               - Cualquier otro valor técnico en inglés debe traducirse a su equivalente natural en español.
            10. Si faltan datos, dilo brevemente.
            11. No inventes datos.

        Consulta del usuario: '{$query}'
        EOT;

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        $response = $client->chat()->create([
            'model' => 'gpt-5.4',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres Mariano, un asistente analítico para un taller de vehículos. Respondes en español, directo y sin rodeos. Cada consulta es independiente: no ofrezcas seguir la conversación ni propongas otras consultas. Puedes usar formato markdown (negritas, tablas, listas) cuando ayude a la legibilidad. Si la respuesta involucra datos estructurados (rankings, tops, comparativas), usa tablas markdown. Nunca inventas datos.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
        ]);

        return response()->json([
            'response' => $response['choices'][0]['message']['content'],
            'query' => $query,
            'sql' => [],
        ]);
    }

    public function getQueryResultsGeminiFlash(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([
                'error' => 'La consulta no puede estar vacía'
            ], 400);
        }

        try {
            $result = $this->boostQueryService->processQueryWithBoostGemini($query);

            return response()->json([
                'response' => $result['response'],
                'query' => $result['query'],
                'sql' => $result['executed_sql'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return $this->getQueryResultsGeminiFlashFallback($request);
        }
    }

    protected function getQueryResultsGeminiFlashFallback(Request $request)
    {
        $repairs = Repair::all();
        $vehicles = Vehicle::all();
        $clients = Client::all();
        $deliveryNotes = DeliveryNote::all();

        $query = $request->input('query');
        
        $repairsData = json_encode($repairs->toArray());
        $vehiclesData = json_encode($vehicles->toArray());
        $clientsData = json_encode($clients->toArray());
        $deliveryNotesData = json_encode($deliveryNotes->toArray());

        $prompt = <<<EOT
        Eres un asistente analítico preciso para un taller de reparación de vehículos. Tu tarea es responder con información exacta basada exclusivamente en los datos proporcionados, con un tono directo y conciso.

        Tienes acceso a estos datos reales:
            - REPARACIONES: $repairsData
            - VEHÍCULOS: $vehiclesData
            - CLIENTES: $clientsData
            - NOTAS DE ENTREGA: $deliveryNotesData

        INSTRUCCIONES IMPORTANTES SOBRE LOS DATOS:
            - En las NOTAS DE ENTREGA (albaranes), el campo "added_at" es la fecha de registro del albarán.
            - Debes utilizar este campo para análisis temporales (diario, mensual, anual).
            - Para análisis por mes, extrae el mes de este campo de fecha.
            - Todos los cálculos de tendencias temporales deben basarse en estas fechas de registro.

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

        Consulta del usuario: '{$query}'
        EOT;

        $model = ModelType::generateGeminiModel(ModelVariation::FLASH, 2.0);
        $response = Gemini::generativeModel($model)->generateContent($prompt);

        return response()->json([
            'response' => $response->text(),
            'query' => $query,
            'sql' => [],
        ]);
    }
}