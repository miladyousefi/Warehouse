<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaAIService
{
    /**
     * Base URL for Ollama API
     */
    private string $baseUrl;

    /**
     * Model to use
     */
    private string $model;

    /**
     * Request timeout in seconds
     */
    private int $timeout;

    /**
     * Maximum tokens for generation
     */
    private int $maxTokens;

    /**
     * Temperature for randomness (0-1)
     */
    private float $temperature;

    /**
     * Last model chosen for a request after availability checks.
     */
    private ?string $resolvedModel = null;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.host', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'deepseek-r1:14b');
        $this->timeout = config('services.ollama.timeout', 300);
        $this->maxTokens = config('services.ollama.max_tokens', 2000);
        $this->temperature = config('services.ollama.temperature', 0.7);
    }

    /**
     * Check if Ollama service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Ollama service unavailable', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate a response from the model
     */
    public function generate(string $prompt, array $options = []): string
    {
        try {
            $model = $this->resolveModel();
            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/api/generate",
                [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => $options['temperature'] ?? $this->temperature,
                        'num_predict' => $options['max_tokens'] ?? $this->maxTokens,
                    ],
                ]
            );

            if ($response->successful()) {
                return $response->json('response', '');
            }

            if ($response->status() === 404 && $model !== $this->model) {
                Log::warning('Ollama fallback model was not found either', [
                    'configured_model' => $this->model,
                    'fallback_model' => $model,
                ]);
            }

            Log::error('Ollama generation failed', [
                'model' => $model,
                'status' => $response->status(),
                'error' => $response->body(),
                'prompt' => substr($prompt, 0, 200),
            ]);

            return '';
        } catch (\Exception $e) {
            Log::error('Ollama request failed', [
                'error' => $e->getMessage(),
                'prompt' => substr($prompt, 0, 200),
            ]);

            return '';
        }
    }

    /**
     * Generate with streaming response (chunked)
     */
    public function generateStream(string $prompt, callable $callback, array $options = []): void
    {
        try {
            $model = $this->resolveModel();
            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/api/generate",
                [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => [
                        'temperature' => $options['temperature'] ?? $this->temperature,
                        'num_predict' => $options['max_tokens'] ?? $this->maxTokens,
                    ],
                ]
            );

            if ($response->successful()) {
                // Handle streaming response
                $lines = explode("\n", $response->body());
                foreach ($lines as $line) {
                    if (empty($line)) continue;
                    $data = json_decode($line, true);
                    if (isset($data['response'])) {
                        $callback($data['response']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Ollama streaming failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate embeddings for text (if model supports it)
     */
    public function embed(string $text): array
    {
        try {
            $model = $this->resolveModel();
            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/api/embed",
                [
                    'model' => $model,
                    'input' => $text,
                ]
            );

            if ($response->successful()) {
                return $response->json('embeddings.0', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Ollama embedding failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get available models from Ollama
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");

            if ($response->successful()) {
                return $response->json('models', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch models', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Set the model to use
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        $this->resolvedModel = null;
        return $this;
    }

    /**
     * Report which model the service will currently use.
     */
    public function getResolvedModel(): string
    {
        return $this->resolvedModel ?? $this->resolveModel();
    }

    /**
     * Set temperature (randomness)
     */
    public function setTemperature(float $temperature): self
    {
        $this->temperature = max(0, min(1, $temperature));
        return $this;
    }

    /**
     * Set max tokens
     */
    public function setMaxTokens(int $tokens): self
    {
        $this->maxTokens = $tokens;
        return $this;
    }

    /**
     * Create a structured prompt for data analysis
     */
    public function analyzeData(string $question, string $dataContext): string
    {
        $prompt = <<<PROMPT
You are an intelligent data analyst for a Restaurant Warehouse Management System.

Based on the following data context, answer the user's question thoughtfully and provide insights.

DATA CONTEXT:
{$dataContext}

USER QUESTION:
{$question}

Please provide:
1. A direct answer to the question
2. Relevant insights from the data
3. Any recommendations or observations

Answer:
PROMPT;

        return $this->generate($prompt, ['max_tokens' => 1500]);
    }

    /**
     * Build a conversational response
     */
    public function conversationalChat(string $message, ?string $conversationHistory = null): string
    {
        $systemPrompt = "You are a helpful assistant for the Restaurant Warehouse Management System. You understand inventory, products, orders, and restaurant operations. Be concise and practical in your responses.";

        $prompt = $systemPrompt;

        if ($conversationHistory) {
            $prompt .= "\n\nPrevious conversation:\n{$conversationHistory}";
        }

        $prompt .= "\n\nUser message: {$message}\n\nAssistant response:";

        return $this->generate($prompt);
    }

    /**
     * Prefer the configured model, but gracefully fall back to an installed one.
     */
    private function resolveModel(): string
    {
        $availableModels = collect($this->getAvailableModels())
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values();

        if ($availableModels->isEmpty()) {
            $this->resolvedModel = $this->model;
            return $this->resolvedModel;
        }

        if ($availableModels->contains($this->model)) {
            $this->resolvedModel = $this->model;
            return $this->resolvedModel;
        }

        $familyMatch = $availableModels->first(
            fn (string $name) => str_starts_with($name, strtok($this->model, ':') . ':')
        );

        $fallbackModel = $familyMatch ?? $availableModels->first();

        Log::warning('Configured Ollama model is unavailable, using fallback', [
            'configured_model' => $this->model,
            'fallback_model' => $fallbackModel,
        ]);

        $this->resolvedModel = $fallbackModel;

        return $this->resolvedModel;
    }
}
