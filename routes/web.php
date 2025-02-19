<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HelpdeskController;
use App\Http\Controllers\FileUploadController;
use App\Models\Department;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;  
use Inertia\Inertia;
use Illuminate\Http\Request;

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    return redirect()->route('login');

});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('verified')->name('dashboard');

    // Helpdesk Routes
    Route::middleware('verified')->group(function () {
        // Replace the existing support route with this:
        Route::get('/helpdesk/support', [HelpdeskController::class, 'support'])
            ->name('helpdesk.support')
            ->middleware('auth');
        
        // Update this route to use the controller
        Route::get('/helpdesk/settings', [HelpdeskController::class, 'settings'])
            ->name('helpdesk.settings')
            ->middleware('admin');

        Route::get('/helpdesk/users', function (Request $request) {
            return Inertia::render('Helpdesk/Users', [
                'isAdmin' => $request->user()->is_admin
            ]);
        })->name('helpdesk.users');
    });

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Add route for refreshing conversations - before the admin middleware group
    Route::get('/helpdesk/conversations/refresh', [HelpdeskController::class, 'getConversations']);

    // Add a dedicated route for refreshing conversations
    Route::get('/helpdesk/conversations/refresh', [HelpdeskController::class, 'getConversations'])
        ->name('helpdesk.conversations.refresh');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('departments', DepartmentController::class);
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])
        ->name('users.password.update');
    
    Route::post('/helpdesk/email/sync', [HelpdeskController::class, 'syncEmails'])
        ->name('helpdesk.email.sync');
    Route::post('/helpdesk/settings/save', [HelpdeskController::class, 'saveSettings'])
        ->name('helpdesk.settings.save');
    
    // Add these new routes
    Route::get('/helpdesk/queue/status', [HelpdeskController::class, 'getQueueStatus'])
        ->name('helpdesk.queue.status');
    Route::post('/helpdesk/queue/restart', [HelpdeskController::class, 'restartWorkers'])
        ->name('helpdesk.queue.restart');
    
    // Add this new route
    Route::get('/helpdesk/cron/status', [HelpdeskController::class, 'getCronStatus'])
        ->name('helpdesk.cron.status');
    
    // Add this new route
    Route::get('/helpdesk/support-users', [HelpdeskController::class, 'getSupportUsers'])
        ->name('helpdesk.users.list');
    
    // Add these new routes
    Route::delete('/helpdesk/users/{user}', [HelpdeskController::class, 'deleteUser'])
        ->name('helpdesk.users.delete');
    Route::get('/helpdesk/users/{user}/edit', [HelpdeskController::class, 'editUser'])
        ->name('helpdesk.users.edit');
    Route::get('/helpdesk/tickets/create', [HelpdeskController::class, 'createTicket'])
        ->name('helpdesk.tickets.create');
    
    // Add the update route
    Route::put('/helpdesk/users/{user}', [HelpdeskController::class, 'updateUser'])
        ->name('helpdesk.users.update');
    
    // Add these new spam management routes
    Route::get('/helpdesk/spam', [HelpdeskController::class, 'getSpamContacts'])
        ->name('helpdesk.spam.list');
    Route::delete('/helpdesk/spam/{spamContact}', [HelpdeskController::class, 'deleteSpamContact'])
        ->name('helpdesk.spam.delete');
    Route::post('/helpdesk/spam', [HelpdeskController::class, 'addSpamContact'])
        ->name('helpdesk.spam.add');
    
    // Add these new assignment routes
    Route::post('/helpdesk/conversations/{conversation}/assign-department', [HelpdeskController::class, 'assignDepartment'])
        ->name('helpdesk.conversations.assign-department');
    Route::post('/helpdesk/conversations/{conversation}/assign-agent', [HelpdeskController::class, 'assignAgent'])
        ->name('helpdesk.conversations.assign-agent');
    
    // Add these new routes
    Route::post('/helpdesk/conversations/{conversation}/archive', [HelpdeskController::class, 'archiveConversation'])
        ->name('helpdesk.conversations.archive');
    
    Route::post('/helpdesk/conversations/{conversation}/unspam', [HelpdeskController::class, 'unmarkSpam'])
        ->name('helpdesk.conversations.unspam');
    
    Route::post('/helpdesk/conversations/{conversation}/unarchive', [HelpdeskController::class, 'unarchiveConversation'])
        ->name('helpdesk.conversations.unarchive');
    
    Route::post('/helpdesk/conversations/{conversation}/status', [HelpdeskController::class, 'updateStatus'])
        ->name('helpdesk.conversations.status');
    
    Route::post('/helpdesk/conversations/{conversation}/read', [HelpdeskController::class, 'markMessagesRead'])
        ->name('helpdesk.conversations.read');
    
    Route::post('/helpdesk/conversations/{conversation}/messages', [HelpdeskController::class, 'sendMessage'])
        ->name('helpdesk.conversations.send-message');
}); // This is the correct closing brace

Route::middleware(['auth'])->group(function () {
    Route::get('/helpdesk/conversations/{conversation}', [HelpdeskController::class, 'getConversation'])
        ->name('helpdesk.conversations.get');
    
    Route::post('/api/upload', [FileUploadController::class, 'store'])
        ->name('api.upload');
});

// Test routes
Route::get('/test-log', function() {
    Log::info('Test log entry');
    file_put_contents(
        storage_path('logs/debug.log'),
        date('Y-m-d H:i:s') . " - Test direct file write\n",
        FILE_APPEND
    );
    return 'Check logs';
});

Route::get('/test-schedule', function() {
    Log::info('Test route executed');
    $schedule = app()->make(Schedule::class);
    $events = collect($schedule->events())->map(fn($event) => [
        'command' => $event->command,
        'expression' => $event->expression,
    ]);
    return [
        'message' => 'Check logs for details',
        'events' => $events,
        'next_run' => $schedule->events()[0]->nextRunDate(),
    ];
});

require __DIR__.'/auth.php';
