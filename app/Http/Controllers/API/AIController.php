<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AIConversation;
use App\Models\AIConversationMessage;
use App\Services\DynamicDataService;
use App\Services\OllamaAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    public function __construct(
        private OllamaAIService $aiService,
        private DynamicDataService $dataService,
    ) {}

    /**
     * Check if AI service is available
     */
    public function status(): JsonResponse
    {
        $isAvailable = $this->aiService->isAvailable();
        $models = $this->aiService->getAvailableModels();

        return response()->json([
            'status' => $isAvailable ? 'online' : 'offline',
            'message' => $isAvailable ? 'AI service is running' : 'AI service is not available',
            'models' => $models ?? [],
            'timestamp' => now(),
        ]);
    }

    /**
     * Simple chat endpoint
     * POST /api/ai/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        if (!$this->aiService->isAvailable()) {
            return response()->json([
                'error' => 'AI service is not available',
                'suggestion' => 'Make sure Ollama is running',
            ], 503);
        }

        try {
            $response = $this->aiService->conversationalChat($request->message);

            if (blank($response)) {
                return response()->json([
                    'error' => 'AI model returned an empty response',
                    'suggestion' => 'Check whether Ollama is running, the selected model is available, or the request timed out.',
                ], 504);
            }

            return response()->json([
                'success' => true,
                'message' => $request->message,
                'response' => $response,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Chat error', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to generate response',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze data with dynamic model selection
     * POST /api/ai/analyze
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'models' => 'array|in:' . implode(',', $this->dataService->getAllowedModels()),
            'limit' => 'integer|min:1|max:100',
        ]);

        if (!$this->aiService->isAvailable()) {
            return response()->json([
                'error' => 'AI service is not available',
            ], 503);
        }

        try {
            $models = $request->models ?? ['Product'];
            $limit = $request->limit ?? 10;

            // Build data context
            $dataContext = $this->dataService->buildDataContext($models, [], $limit);

            // Analyze with AI
            $analysis = $this->aiService->analyzeData($request->question, $dataContext);

            return response()->json([
                'success' => true,
                'question' => $request->question,
                'models_queried' => $models,
                'analysis' => $analysis,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Analysis error', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to analyze data',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse natural language and get intended models/filters
     * POST /api/ai/parse-query
     */
    public function parseQuery(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:1000',
        ]);

        try {
            $parsed = $this->dataService->parseQuery($request->query);

            return response()->json([
                'success' => true,
                'query' => $request->query,
                'parsed' => $parsed,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to parse query',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available models for querying
     * GET /api/ai/models
     */
    public function getModels(): JsonResponse
    {
        $models = [];
        foreach ($this->dataService->getAllowedModels() as $modelName) {
            $models[] = $this->dataService->getModelDetails($modelName);
        }

        return response()->json([
            'models' => $models,
            'total' => count($models),
        ]);
    }

    /**
     * Create or get conversation
     * POST /api/ai/conversation
     */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'string|max:255',
        ]);

        try {
            $conversation = AIConversation::create([
                'user_id' => auth()->id(),
                'title' => $request->title ?? 'New Conversation',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $conversation,
                    'id' => (string) $conversation->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create conversation',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add message to conversation
     * POST /api/ai/conversation/{conversationId}/message
     */
    public function sendMessage(Request $request, string $conversationId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'include_data' => 'nullable|string',
        ]);

        if (!$this->aiService->isAvailable()) {
            return response()->json(['error' => 'AI service offline'], 503);
        }

        try {
            $conversation = AIConversation::where('user_id', auth()->id())
                ->findOrFail($conversationId);

            // Save user message
            AIConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $request->message,
            ]);

            $directResponse = $this->dataService->answerDirectly($request->message);

            if (filled($directResponse)) {
                AIConversationMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $directResponse,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'conversation_id' => $conversation->id,
                        'response' => $directResponse,
                    ],
                    'timestamp' => now(),
                ]);
            }

            // Build context based on include_data parameter
            $dataContext = '';
            $includeData = $request->include_data;

            if ($includeData === 'auto') {
                // Auto-detect relevant data based on user question
                $dataContext = $this->dataService->autoDetectData($request->message);
            } elseif ($includeData === 'true' || $includeData === true) {
                // Use all default models
                $models = $request->models ?? ['Product', 'ProductCategory', 'PurchaseOrder'];
                $dataContext = $this->dataService->buildDataContext($models, [], 10);
            }

            // Get conversation history for context
            $history = $conversation->messages()
                ->latest()
                ->limit(3)
                ->get()
                ->reverse()
                ->map(fn ($msg) => "{$msg->role}: {$msg->content}")
                ->implode("\n");

            // Keep general chat lightweight; only use the raw generation path when data context is needed.
            if (filled($dataContext)) {
                $prompt = $dataContext . "\n\n" . $history . "\n\nAssistant:";
                $response = $this->aiService->generate($prompt);
            } else {
                $response = $this->aiService->conversationalChat($request->message, $history);
            }

            if (blank($response)) {
                return response()->json([
                    'error' => 'AI model returned an empty response',
                    'suggestion' => 'Check whether Ollama is running, the configured model exists, or the request timed out.',
                ], 504);
            }

            // Save AI response
            AIConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'response' => $response,
                ],
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Conversation error', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to process message',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation history
     * GET /api/ai/conversation/{conversationId}
     */
    public function getConversation(string $conversationId): JsonResponse
    {
        try {
            $conversation = AIConversation::with('messages')
                ->where('user_id', auth()->id())
                ->findOrFail($conversationId);

            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Conversation not found',
            ], 404);
        }
    }

    /**
     * List user's conversations
     * GET /api/ai/conversations
     */
    public function listConversations(): JsonResponse
    {
        try {
            $conversations = AIConversation::where('user_id', auth()->id())
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch conversations',
            ], 500);
        }
    }
}
