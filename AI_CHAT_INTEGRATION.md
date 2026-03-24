# 🔧 AI Chat UI Integration Guide

## How to Integrate AI Chat into Your Application

### Step 1: Register Routes

Add this to your `routes/web.php`:

```php
// Add this at the bottom of routes/web.php
require base_path('routes/ai.php');
```

Or merge the contents of `routes/ai.php` into your existing file.

---

### Step 2: Add Sidebar Menu Item

Find your main layout file (usually `resources/js/Layouts/AppLayout.vue` or similar) and locate the sidebar/navigation section.

**If using a sidebar component:**

```vue
<template>
  <div class="sidebar">
    <!-- Your existing menu items -->
    
    <!-- Add this component -->
    <AISidebarMenu />
  </div>
</template>

<script setup>
import AISidebarMenu from '@/Components/Sidebar/AISidebarMenu.vue'
</script>
```

**If using a traditional navigation list:**

Copy the HTML from `resources/js/Components/Sidebar/AISidebarMenu.vue` and adapt it to your sidebar structure.

**Example integration:**

```vue
<template>
  <div class="sidebar">
    <!-- Existing Dashboard link -->
    <Link href="/dashboard" class="nav-item">Dashboard</Link>
    
    <!-- Existing Products, Orders, etc -->
    <Link href="/products" class="nav-item">Products</Link>
    
    <!-- ADD THIS: -->
    <div class="nav-section">
      <h3 class="section-title">AI & Analytics</h3>
      <Link href="/ai/chat" class="nav-item">
        <svg class="icon"><!-- chat icon --></svg>
        AI Assistant
      </Link>
    </div>
  </div>
</template>
```

---

### Step 3: Update User Model for Token

Ensure your User model has Sanctum token support:

```php
<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens;
    // ... rest of model
}
```

---

### Step 4: Create Authentication Token

When user logs in, create a Sanctum token. In your login controller:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        // ... existing validation
        
        auth()->login($user);
        
        // Create Sanctum token
        $token = $user->createToken('api')->plainTextToken;
        
        // Store in session for JavaScript access
        session(['sanctum_token' => $token]);
        
        return redirect('/dashboard');
    }
}
```

---

### Step 5: Make Token Available to Vue

In your Inertia layout, ensure the token is available:

```php
<?php

namespace App\Http\Middleware;

use Closure;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                // Add this line:
                'sanctum_token' => $request->session()->get('sanctum_token'),
            ],
        ]);
    }
}
```

Or in your AI Chat.vue component, it will try to get from:
1. Page props: `page.props.auth.sanctum_token`
2. LocalStorage: `localStorage.getItem('sanctum_token')`
3. Prompt user to log in if not found

---

### Step 6 (Optional): Add Global Navigation

To make AI Chat accessible from anywhere, add a floating button:

**Create `resources/js/Components/FloatingAIButton.vue`:**

```vue
<template>
  <Link
    href="/ai/chat"
    class="fixed bottom-6 right-6 flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition"
    title="Open AI Assistant"
  >
    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>
```

Add to your main layout:

```vue
<template>
  <div>
    <!-- Your main content -->
    <main><!-- ... --></main>
    
    <!-- Add floating button -->
    <FloatingAIButton />
  </div>
</template>

<script setup>
import FloatingAIButton from '@/Components/FloatingAIButton.vue'
</script>
```

---

### Step 7: Customize Styling (Optional)

The Chat component uses Tailwind CSS. If you need to customize:

**Edit `resources/js/Pages/AI/Chat.vue`** to change colors, spacing, etc.

Common customizations:

```vue
<!-- Change primary color from blue to your brand color -->
<button class="bg-blue-600 hover:bg-blue-700">...</button>
<!-- Change to: -->
<button class="bg-purple-600 hover:bg-purple-700">...</button>

<!-- Change header background -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800">...</div>
<!-- Change to: -->
<div class="bg-gradient-to-r from-green-600 to-green-800">...</div>
```

---

## 📋 File Checklist

After integration, ensure these files are in place:

- ✅ `resources/js/Pages/AI/Chat.vue` - Main chat component
- ✅ `resources/js/Components/Sidebar/AISidebarMenu.vue` - Sidebar menu
- ✅ `routes/ai.php` - Page routes
- ✅ `app/Http/Controllers/AI/ChatController.php` - Page controller
- ✅ Routes registered in `routes/web.php`
- ✅ Sidebar menu added to layout
- ✅ Sanctum token properly configured

---

## 🧪 Testing

1. **Log in to your application**
2. **Click "AI Assistant" in the sidebar**
3. **Type a message and send**
4. **Verify:**
   - Message appears in chat
   - AI responds (after model downloads)
   - Errors are displayed clearly

---

## 🆘 Troubleshooting

### Chat page shows "Authentication token not found"

**Fix:**
```php
// In ChatController.php or middleware, ensure token is set:
session(['sanctum_token' => auth()->user()->createToken('api')->plainTextToken]);
```

### "Cannot POST /api/ai/chat" error

**Fix:**
1. Ensure `routes/api.php` includes AI routes
2. Check that APIController exists
3. Verify CORS middleware if using separate domain

### Chat data not persisting

**Fix:**
1. Run migrations: `php artisan migrate`
2. Check database tables exist: `ai_conversations`, `ai_conversation_messages`
3. Verify user_id is being saved

### Styling issues with Tailwind

**Fix:**
1. Ensure Tailwind CSS is built: `npm run dev`
2. Check `tailwind.config.js` includes Vue file patterns
3. Rebuild CSS: `npm run build`

---

## 🚀 Next Steps

1. **Customize styling** to match your brand
2. **Add more data sources** by editing `selectedModels` in Chat.vue
3. **Create custom prompts** in DynamicDataService
4. **Add reports page** using a similar pattern
5. **Deploy to production** using Docker

---

## 📚 Related Documentation

- [API_REFERENCE.md](../API_REFERENCE.md) - AI API endpoints
- [EXTENSION_GUIDE.md](../EXTENSION_GUIDE.md) - Advanced customization
- [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md) - Production setup
