# Extension & Customization Guide

## 📚 Adding New Models to AI Queries

### Step 1: Add Model to Whitelist

Edit [app/Services/DynamicDataService.php](app/Services/DynamicDataService.php):

```php
private const ALLOWED_MODELS = [
    'Product',
    'ProductCategory',
    // ... existing models ...
    'YourNewModel',  // Add here
];
```

### Step 2: Define Model Fields (Optional)

The service auto-detects fields from your model, but you can customize:

```php
/**
 * Get columns/fields available in a model
 */
public function getModelFields(string $modelName): array
{
    if (!$this->isAllowedModel($modelName)) {
        return [];
    }

    try {
        $modelClass = $this->getModelClass($modelName);
        $model = new $modelClass();

        // Get fillable fields
        $fields = $model->getFillable() ?: [];
        
        // Add custom fields if needed
        if ($modelName === 'YourNewModel') {
            $fields[] = 'custom_field';
            $fields[] = 'another_field';
        }

        return array_unique($fields);
    } catch (\Exception $e) {
        return [];
    }
}
```

### Step 3: Test It

```bash
# Get available models
curl -X GET http://localhost:8000/api/ai/models

# Use in analysis
curl -X POST http://localhost:8000/api/ai/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Show data from YourNewModel",
    "models": ["YourNewModel"],
    "limit": 10
  }'
```

---

## 🎨 Custom Prompts & Analysis

### Modify AI Behavior

Edit [app/Services/OllamaAIService.php](app/Services/OllamaAIService.php):

```php
/**
 * Create a structured prompt for data analysis
 */
public function analyzeData(string $question, string $dataContext): string
{
    $prompt = <<<PROMPT
You are an intelligent data analyst for a Restaurant Warehouse Management System.

System Instructions:
- Provide accurate, data-driven insights
- Show calculations and reasoning
- Suggest actionable recommendations
- Format responses clearly with sections
- Include confidence levels for predictions
- Cite specific data points used

DATA CONTEXT:
{$dataContext}

USER QUESTION:
{$question}

Please provide:
1. Direct answer with evidence
2. Key insights and patterns
3. Actionable recommendations
4. Potential risks or concerns

Answer:
PROMPT;

    return $this->generate($prompt, ['max_tokens' => 1500]);
}
```

### Create Custom Analysis Method

```php
/**
 * Specialized analysis for specific domain
 */
public function inventoryCostAnalysis(string $dataContext): string
{
    $prompt = <<<PROMPT
You are a supply chain and inventory cost specialist.

Analyze the warehouse inventory data and provide:
1. Cost optimization opportunities
2. Excess inventory that should be liquidated
3. High-value items that need better control
4. Seasonal trends in inventory
5. ROI recommendations for restocking

DATA:
{$dataContext}

Analysis:
PROMPT;

    return $this->generate($prompt);
}
```

Then expose via API:

```php
// In AIController
public function inventoryAnalysis(Request $request): JsonResponse
{
    $models = ['Product', 'PurchaseOrder', 'PurchaseOrderItem'];
    $dataContext = $this->dataService->buildDataContext($models, [], 20);
    
    $analysis = $this->aiService->inventoryCostAnalysis($dataContext);
    
    return response()->json([
        'analysis_type' => 'inventory_cost',
        'analysis' => $analysis,
    ]);
}
```

---

## 🔌 Add Custom Filters

Enhance query parsing in [app/Services/DynamicDataService.php](app/Services/DynamicDataService.php):

```php
/**
 * Parse a natural language query to extract models and filters
 */
public function parseQuery(string $naturalLanguageQuery): array
{
    $result = [
        'models' => [],
        'filters' => [],
    ];

    $query = strtolower($naturalLanguageQuery);

    // ... existing code ...

    // Add custom filter patterns
    if (preg_match('/status\s*[=:]+\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
        $result['filters']['status'] = ['operator' => '=', 'value' => trim($matches[1])];
    }

    if (preg_match('/date\s*between\s*(\d{4}-\d{2}-\d{2})\s*and\s*(\d{4}-\d{2}-\d{2})/i', $query, $matches)) {
        $result['filters']['date_from'] = ['operator' => '>=', 'value' => $matches[1]];
        $result['filters']['date_to'] = ['operator' => '<=', 'value' => $matches[2]];
    }

    return $result;
}
```

---

## 🧠 Fine-tune Temperature & Tokens

### For Different Use Cases

```php
// Deterministic responses (inventory counts)
private const CONFIG = [
    'deterministic' => [
        'temperature' => 0.2,
        'max_tokens' => 500,
    ],
    'creative' => [
        'temperature' => 0.9,
        'max_tokens' => 3000,
    ],
    'balanced' => [
        'temperature' => 0.7,
        'max_tokens' => 1500,
    ],
];

// In controller
public function analyzeCreative(Request $request): JsonResponse
{
    $this->aiService->setTemperature(0.9);
    $this->aiService->setMaxTokens(3000);
    
    $response = $this->aiService->generate($prompt);
    return response()->json(['response' => $response]);
}
```

---

## 📊 Add Conversation Metadata

Track conversation context:

```php
// In AIController
public function startConversation(Request $request): JsonResponse
{
    $conversation = AIConversation::create([
        'user_id' => auth()->id(),
        'title' => $request->title ?? 'New Conversation',
        'metadata' => [
            'focus_models' => $request->models ?? ['Product'],
            'analysis_type' => $request->type ?? 'general',
            'started_by' => auth()->user()->name,
            'custom_context' => $request->context,
        ],
    ]);

    return response()->json(['conversation' => $conversation]);
}

// Later retrieve and use metadata
$conversation = AIConversation::find($id);
$focusModels = $conversation->metadata['focus_models'] ?? [];
```

---

## 🚀 Add Streaming Responses

For real-time long responses:

```php
// In AIController
public function streamChat(Request $request): StreamedResponse
{
    return response()->stream(function () use ($request) {
        echo "data: " . json_encode(['status' => 'processing']) . "\n\n";
        
        $this->aiService->generateStream(
            $request->message,
            function ($chunk) {
                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                flush();
            }
        );
        
        echo "data: " . json_encode(['status' => 'complete']) . "\n\n";
    }, 200, [
        'Cache-Control' => 'no-cache',
        'Content-Type' => 'text/event-stream',
        'X-Accel-Buffering' => 'no',
    ]);
}

// Route
Route::post('chat/stream', [AIController::class, 'streamChat']);
```

JavaScript client:
```javascript
const response = await fetch('/api/ai/chat/stream', {
    method: 'POST',
    body: JSON.stringify({ message: 'Your question' }),
});

const reader = response.body.getReader();
const decoder = new TextDecoder();

while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    
    const text = decoder.decode(value);
    const lines = text.split('\n');
    
    for (const line of lines) {
        if (line.startsWith('data: ')) {
            const data = JSON.parse(line.slice(6));
            console.log(data);
            if (data.chunk) {
                displayChunk(data.chunk);
            }
        }
    }
}
```

---

## 📝 Add Request Logging

Log all AI interactions for analytics:

```php
// In AIController or Middleware
public function logAIRequest($type, $input, $output, $duration)
{
    AIRequestLog::create([
        'user_id' => auth()->id(),
        'type' => $type,  // 'chat', 'analyze', etc
        'input' => substr($input, 0, 1000),
        'output' => substr($output, 0, 1000),
        'tokens_used' => $this->estimateTokens($output),
        'duration_ms' => $duration,
        'model' => config('services.ollama.model'),
    ]);
}

// Migration
Schema::create('ai_request_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id');
    $table->string('type');
    $table->text('input');
    $table->text('output');
    $table->integer('tokens_used')->default(0);
    $table->integer('duration_ms')->default(0);
    $table->string('model');
    $table->timestamps();
});
```

---

## 🔐 Add Role-Based Permissions

Control AI access by role:

```php
// In AIController
public function chat(Request $request): JsonResponse
{
    // Check if user can use AI chat
    if (!auth()->user()->can('ai.chat')) {
        return response()->json([
            'error' => 'You do not have permission to use this feature'
        ], 403);
    }

    // ... rest of logic
}

// Middleware
Route::middleware(['auth:sanctum', 'can:ai.access'])->prefix('ai')->group(function () {
    // All AI routes here
});
```

---

## 🧪 Testing

```php
// tests/Feature/AIFeatureTest.php
namespace Tests\Feature;

use Tests\TestCase;

class AIFeatureTest extends TestCase
{
    public function test_ai_status_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/ai/status');
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
    }

    public function test_chat_requires_authentication()
    {
        $response = $this->postJson('/api/ai/chat', [
            'message' => 'Test'
        ]);
        
        $response->assertStatus(401);
    }

    public function test_analyze_with_models()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/ai/analyze', [
                'question' => 'Show products',
                'models' => ['Product'],
                'limit' => 5
            ]);
        
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }
}
```

Run tests:
```bash
php artisan test tests/Feature/AIFeatureTest.php
```

---

## 🔄 Background Job Processing

For heavy analysis:

```php
// app/Jobs/AnalyzeDataJob.php
namespace App\Jobs;

use App\Services\DynamicDataService;
use App\Services\OllamaAIService;

class AnalyzeDataJob implements ShouldQueue
{
    public function __construct(
        private string $question,
        private array $models,
    ) {}

    public function handle()
    {
        $dataService = app(DynamicDataService::class);
        $aiService = app(OllamaAIService::class);

        $context = $dataService->buildDataContext($this->models, [], 20);
        $analysis = $aiService->analyzeData($this->question, $context);

        // Store result
        AIAnalysisResult::create([
            'user_id' => auth()->id(),
            'question' => $this->question,
            'result' => $analysis,
        ]);
    }
}

// Dispatch from controller
dispatch(new AnalyzeDataJob($question, $models));
```

---

## 📈 Analytics Dashboard

Track AI usage:

```php
// AIAnalyticsController.php
public function dashboard()
{
    $stats = [
        'total_conversations' => AIConversation::count(),
        'total_messages' => AIConversationMessage::count(),
        'active_users' => AIConversation::distinct('user_id')->count(),
        'average_conversation_length' => AIConversationMessage::groupBy('conversation_id')
            ->selectRaw('COUNT(*) as count')
            ->avg('count'),
    ];

    return response()->json($stats);
}
```

---

## 🎓 Examples Repository

Create example queries users can reference:

```php
// ExampleQueries Model
$examples = ExampleQuery::where('category', 'inventory')->get();

// API endpoint
Route::get('examples/{category?}', function ($category = null) {
    $query = ExampleQuery::query();
    
    if ($category) {
        $query->where('category', $category);
    }
    
    return response()->json($query->get());
});
```

Database seeding:
```php
ExampleQuery::create([
    'category' => 'inventory',
    'question' => 'What products are below safety stock?',
    'description' => 'Identifies products that need reordering',
    'models' => ['Product'],
]);
```

---

## 🔗 Integration Points

You can now integrate AI features into existing parts of your app:

- **Product Management**: Auto-generate descriptions using AI
- **Inventory Alerts**: Use AI to predict stockouts
- **Report Generation**: Create dynamic reports
- **Recommendation Engine**: Suggest products based on data
- **Chatbot Integration**: Deploy conversational interface
- **Process Automation**: Automate repetitive tasks

See individual module documentation for specific integrations.
