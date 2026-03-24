<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Permission\Models\Role;

class DynamicDataService
{
    /**
     * Allowed models for AI querying (whitelist)
     */
    private const ALLOWED_MODELS = [
        'Product',
        'ProductCategory',
        'PurchaseOrder',
        'PurchaseOrderItem',
        'RestaurantMenuItem',
        'RestaurantMenuCategory',
        'RestaurantMenuItemIngredient',
        'User',
        'ActivityLog',
    ];

    /**
     * Natural-language aliases for supported data entities.
     */
    private const MODEL_ALIASES = [
        'Product' => ['product', 'products', 'item', 'items', 'sku', 'skus'],
        'ProductCategory' => ['category', 'categories', 'product category', 'product categories', 'category table', 'categories table'],
        'PurchaseOrder' => ['purchase order', 'purchase orders', 'po', 'pos', 'order', 'orders'],
        'PurchaseOrderItem' => ['purchase order item', 'purchase order items', 'order item', 'order items'],
        'RestaurantMenuItem' => ['menu item', 'menu items'],
        'RestaurantMenuCategory' => ['menu category', 'menu categories'],
        'RestaurantMenuItemIngredient' => ['ingredient', 'ingredients'],
        'User' => ['user', 'users', 'admin', 'admins', 'super admin', 'super admins'],
        'ActivityLog' => ['activity log', 'activity logs', 'log', 'logs'],
    ];

    /**
     * Build a data context from multiple models and filters
     */
    public function buildDataContext(array $modelNames, array $filters = [], int $limit = 10): string
    {
        $context = "# Data Context\n\n";

        foreach ($modelNames as $modelName) {
            if (!$this->isAllowedModel($modelName)) {
                continue;
            }

            $modelClass = $this->getModelClass($modelName);
            $data = $this->queryModel($modelClass, $filters, $limit);

            $context .= $this->formatModelData($modelName, $data);
        }

        return $context;
    }

    /**
     * Get data from a specific model based on filters
     */
    public function queryModel(string $modelClass, array $filters = [], int $limit = 10): array
    {
        try {
            $query = $modelClass::query();

            // Apply filters
            foreach ($filters as $field => $value) {
                $this->applyFilter($query, $field, $value);
            }

            return $query
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::error("Error querying {$modelClass}", ['error' => $e->getMessage()]);
            return [];
        }
    }

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

            // Add timestamps
            if ($model->usesTimestamps()) {
                $fields[] = $model->getCreatedAtColumn();
                $fields[] = $model->getUpdatedAtColumn();
            }

            // Get keys
            $fields[] = $model->getKeyName();

            return array_unique($fields);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all allowed models
     */
    public function getAllowedModels(): array
    {
        return self::ALLOWED_MODELS;
    }

    /**
     * Get model details for schema exploration
     */
    public function getModelDetails(string $modelName): array
    {
        if (!$this->isAllowedModel($modelName)) {
            return [];
        }

        try {
            $modelClass = $this->getModelClass($modelName);
            $model = new $modelClass();

            return [
                'name' => $modelName,
                'table' => $model->getTable(),
                'fields' => $this->getModelFields($modelName),
                'keyName' => $model->getKeyName(),
                'timestamps' => $model->usesTimestamps(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Parse a natural language query to extract models and filters
     * Example: "Show me products from Beverages with price > 100"
     */
    public function parseQuery(string $naturalLanguageQuery): array
    {
        $result = [
            'models' => [],
            'filters' => [],
        ];

        $query = strtolower($naturalLanguageQuery);

        $result['models'] = $this->detectModels($query);

        // Extract simple filters (this is basic - can be enhanced)
        if (preg_match('/price\s*[>>=<]+\s*(\d+\.?\d*)/i', $query, $matches)) {
            $result['filters']['price'] = ['operator' => '>', 'value' => (float) $matches[1]];
        }

        if (preg_match('/stock\s*[>>=<]+\s*(\d+)/i', $query, $matches)) {
            $result['filters']['stock_quantity'] = ['operator' => '>', 'value' => (int) $matches[1]];
        }

        if (preg_match('/category\s*[=:]+\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
            $result['filters']['category'] = ['operator' => '=', 'value' => trim($matches[1])];
        }

        return $result;
    }

    /**
     * Auto-detect which models and data are needed based on user question
     */
    public function autoDetectData(string $userQuery): string
    {
        // Parse the query to find relevant models and filters
        $parsed = $this->parseQuery($userQuery);

        if (empty($parsed['models']) && empty($parsed['filters'])) {
            return '';
        }
        
        // Keep the context lean enough for local models to answer quickly.
        return $this->buildDataContext($parsed['models'], $parsed['filters'], 3);
    }

    /**
     * Answer simple aggregate questions directly from the database.
     */
    public function answerDirectly(string $userQuery): ?string
    {
        $adminAnswer = $this->answerAdminQuery($userQuery);
        if (filled($adminAnswer)) {
            return $adminAnswer;
        }

        $parsed = $this->parseQuery($userQuery);

        if (empty($parsed['models']) || ! $this->isCountQuery($userQuery)) {
            return null;
        }

        $responses = [];

        try {
            foreach ($parsed['models'] as $modelName) {
                if (! $this->isAllowedModel($modelName)) {
                    continue;
                }

                $count = $this->getModelClass($modelName)::count();
                $responses[] = sprintf(
                    'There %s %d %s in the system.',
                    $count === 1 ? 'is' : 'are',
                    $count,
                    $this->displayModelLabel($modelName, $count)
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to answer count query directly', [
                'query' => $userQuery,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return empty($responses) ? null : implode("\n", $responses);
    }

    /**
     * ============ Private Helpers ============
     */

    /**
     * Check if model is in whitelist
     */
    private function isAllowedModel(string $modelName): bool
    {
        return in_array($modelName, self::ALLOWED_MODELS);
    }

    /**
     * Detect supported models from a natural-language query using aliases.
     */
    private function detectModels(string $query): array
    {
        $models = [];

        foreach (self::MODEL_ALIASES as $model => $aliases) {
            if (Str::contains($query, $aliases)) {
                $models[] = $model;
            }
        }

        foreach (self::ALLOWED_MODELS as $model) {
            $modelLower = strtolower($model);
            if (Str::contains($query, [$modelLower, Str::plural($modelLower)])) {
                $models[] = $model;
            }
        }

        return array_values(array_unique($models));
    }

    /**
     * Get full class name for model
     */
    private function getModelClass(string $modelName): string
    {
        return "App\\Models\\{$modelName}";
    }

    /**
     * Apply filter to query
     */
    private function applyFilter(&$query, string $field, $value): void
    {
        if (is_array($value) && isset($value['operator'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            match ($operator) {
                '>' => $query->where($field, '>', $filterValue),
                '<' => $query->where($field, '<', $filterValue),
                '>=' => $query->where($field, '>=', $filterValue),
                '<=' => $query->where($field, '<=', $filterValue),
                '!=' => $query->where($field, '!=', $filterValue),
                default => $query->where($field, '=', $filterValue),
            };
        } else {
            $query->where($field, '=', $value);
        }
    }

    /**
     * Format model data for AI context
     */
    private function formatModelData(string $modelName, array $data): string
    {
        if (empty($data)) {
            return "## {$modelName}\nNo data found.\n\n";
        }

        $output = "## {$modelName}\n";
        $output .= "**Count**: " . count($data) . " records\n";
        $output .= "**Fields**: " . implode(', ', array_keys($data[0])) . "\n\n";

        // Show first few records in table-like format
        $output .= "| " . implode(" | ", array_keys($data[0])) . " |\n";
        $output .= "|" . implode("|", array_fill(0, count($data[0]), " --- ")) . "|\n";

        foreach (array_slice($data, 0, 3) as $record) {
            $values = array_map(fn ($v) => is_null($v) ? 'NULL' : substr((string) $v, 0, 20), $record);
            $output .= "| " . implode(" | ", $values) . " |\n";
        }

        if (count($data) > 3) {
            $remaining = count($data) - 3;
            $output .= "| ... ({$remaining} more records) |\n";
        }

        $output .= "\n";

        return $output;
    }

    /**
     * Detect simple count-style questions.
     */
    private function isCountQuery(string $query): bool
    {
        $normalized = strtolower($query);

        return Str::contains($normalized, [
            'count',
            'how many',
            'number of',
            'total ',
            'total?',
            'do i have',
            'we have',
            'there are',
            'there is',
        ]);
    }

    /**
     * Answer direct user/admin permission questions without going through AI generation.
     */
    private function answerAdminQuery(string $query): ?string
    {
        $normalized = strtolower($query);

        if (! Str::contains($normalized, ['super admin', 'admin'])) {
            return null;
        }

        if (! Str::contains($normalized, ['name', 'permission', 'permissions', 'role'])) {
            return null;
        }

        try {
            $adminRole = Role::query()
                ->where('guard_name', 'web')
                ->where('name', 'admin')
                ->with('permissions:id,name')
                ->first();

            $adminUser = User::query()
                ->role('admin')
                ->orderBy('id')
                ->first(['id', 'name', 'email']);

            $parts = [];

            if ($adminUser && Str::contains($normalized, ['name', 'super admin'])) {
                $parts[] = "Super admin: {$adminUser->name} ({$adminUser->email})";
            }

            if ($adminRole && Str::contains($normalized, ['permission', 'permissions', 'role'])) {
                $permissions = $adminRole->permissions
                    ->pluck('name')
                    ->sort()
                    ->values();

                $parts[] = "Admin role: {$adminRole->name}";
                $parts[] = "Permissions (" . $permissions->count() . '): ' . $permissions->implode(', ');
            }

            return empty($parts) ? null : implode("\n", $parts);
        } catch (\Exception $e) {
            \Log::error('Failed to answer admin query directly', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Human-readable labels for model names.
     */
    private function displayModelLabel(string $modelName, int $count): string
    {
        return match ($modelName) {
            'Product' => Str::plural('product', $count),
            'ProductCategory' => Str::plural('category', $count),
            'PurchaseOrder' => Str::plural('purchase order', $count),
            'PurchaseOrderItem' => Str::plural('purchase order item', $count),
            'RestaurantMenuItem' => Str::plural('menu item', $count),
            'RestaurantMenuCategory' => Str::plural('menu category', $count),
            'RestaurantMenuItemIngredient' => Str::plural('ingredient', $count),
            'User' => Str::plural('user', $count),
            'ActivityLog' => Str::plural('activity log', $count),
            default => Str::plural(strtolower($modelName), $count),
        };
    }
}
