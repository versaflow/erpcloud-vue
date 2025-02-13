<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use App\Models\Department;
use App\Models\EmailSignature;
use App\Models\SupportUser;
use App\Models\Conversation;
use App\Models\User;
use App\Jobs\FetchImapEmails;  // Add this import
use App\Models\SpamContact;
use Illuminate\Http\Request;

use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HelpdeskController extends Controller
{
    public function settings()
    {
        return Inertia::render('Helpdesk/Settings', [
            'departments' => Department::withCount('users')
                ->select('id', 'name', 'email', 'is_active')
                ->get(),
            'emailSettings' => [
                'imap_accounts' => EmailSetting::with('department')->get(),
                'smtp_config' => DB::table('smtp_settings')->first(),
                'signatures' => EmailSignature::all()
            ]
        ]);
    }

    public function getQueueStatus()
    {
        return response()->json([
            'jobs' => Queue::size('default'),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'last_cron_run' => cache()->get('last_cron_run')?->diffForHumans(),
            'last_sync_at' => EmailSetting::max('last_sync_at')?->diffForHumans()
        ]);
    }

    public function getCronStatus()
    {
        return response()->json([
            'last_run' => cache()->get('last_cron_run')?->diffForHumans()
        ]);
    }

    public function syncEmails(Request $request)
    {
        try {
            $emailSetting = EmailSetting::findOrFail($request->email_id);
            
            // Just dispatch for immediate sync
            FetchImapEmails::dispatchSync($emailSetting);
            
            return response()->json([
                'message' => 'Email sync completed',
                'last_sync' => now()->diffForHumans()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'imap_configs' => 'array',
            'imap_configs.*.email' => 'required|email',
            'imap_configs.*.host' => 'required',
            'imap_configs.*.port' => 'required|numeric',
            'smtp_config' => 'array',
            'smtp_config.from_name' => 'required|string',
            'smtp_config.email' => 'required|email',
            'smtp_config.host' => 'required|string',
            'smtp_config.port' => 'required|numeric',
            'smtp_config.username' => 'required|string',
            'smtp_config.password' => 'required|string',
            'smtp_config.encryption' => 'required|string',
            'signatures' => 'array',
            'signatures.*.name' => 'required|string',
            'signatures.*.content' => 'required|string',
            'signatures.*.is_default' => 'boolean',
            'email_settings' => 'array'
        ]);

        DB::transaction(function () use ($validated) {
            // Save IMAP configs
            foreach ($validated['imap_configs'] as $config) {
                EmailSetting::updateOrCreate(
                    ['email' => $config['email']],
                    $config
                );
            }

            // Save SMTP config
            DB::table('smtp_settings')->updateOrInsert(
                ['id' => 1],
                $validated['smtp_config']
            );

            // Save signatures
            foreach ($validated['signatures'] as $signature) {
                EmailSignature::updateOrCreate(
                    ['name' => $signature['name']],
                    [
                        'content' => $signature['content'],
                        'is_default' => $signature['is_default']
                    ]
                );
            }
        });

        return redirect()->back()->with('success', 'Settings saved successfully');
    }

    public function support(Request $request)
    {
        // Get authenticated user
        $authUser = Auth::user();
        
        Log::info('User accessing support:', [
            'id' => $authUser->id,
            'is_admin' => $authUser->is_admin,
            'is_agent' => $authUser->is_agent
        ]);

        // Get departments with debug logging
        $departments = Department::select('id', 'name', 'email')
            ->where('is_active', true)
            ->get();
        
        Log::info('Departments found:', [
            'count' => $departments->count(),
            'departments' => $departments->toArray()
        ]);

        // Get agents with debug logging
        $agents = User::where(function($query) {
                $query->where('is_agent', true)
                      ->orWhere('is_admin', true);
            })
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'is_admin', 'department_id', 'status')
            ->with('department:id,name')
            ->get();

        // Return with logged data
        $response = [
            'conversations' => Conversation::with([
                'supportUser' => function($query) {
                    // Load all support user fields
                    $query->select([
                        'id', 'name', 'email', 'phone', 'company',
                        'location', 'timezone', 'tags', 'notes',
                        'last_seen_at', 'created_at'
                    ]);
                },
                'department',
                'messages.attachments'
            ])
            ->latest()
            ->get()
            ->map(fn($conversation) => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'status' => $conversation->status,
                'from_email' => $conversation->from_email,
                'to_email' => $conversation->to_email,
                'created_at' => $conversation->created_at->diffForHumans(),
                'department' => $conversation->department?->name,
                'department_id' => $conversation->department_id,
                'agent_id' => $conversation->agent_id,
                'user' => [
                    'id' => $conversation->supportUser->id,
                    'name' => $conversation->supportUser->name,
                    'email' => $conversation->supportUser->email,
                    'initials' => substr($conversation->supportUser->name, 0, 2),
                    'phone' => $conversation->supportUser->phone,
                    'company' => $conversation->supportUser->company,
                    'location' => $conversation->supportUser->location,
                    'timezone' => $conversation->supportUser->timezone,
                    'tags' => $conversation->supportUser->tags,
                    'notes' => $conversation->supportUser->notes,
                    'created_at' => $conversation->supportUser->created_at->format('Y-m-d'),
                    'total_conversations' => $conversation->supportUser->conversations()->count(),
                    'last_contact' => $conversation->supportUser->last_seen_at?->diffForHumans()
                ],
                'messages' => $conversation->messages->map(fn($message) => [
                    'id' => $message->id,
                    'type' => $message->type,
                    'direction' => $message->direction,
                    'content' => $message->content,
                    'from_name' => $message->from_name,
                    'from_email' => $message->from_email,
                    'to_email' => $message->to_email,
                    'cc' => $message->cc,
                    'subject' => $message->subject,
                    'quoted_text' => $message->quoted_text,
                    'signature' => $message->signature,
                    'tags' => $message->tags,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'has_attachments' => $message->attachments()->exists(),
                    'attachments' => $message->attachments->map(fn($attachment) => [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'size' => $this->formatFileSize($attachment->size),
                        'url' => $attachment->url,
                        'type' => $attachment->type
                    ])
                ])
            ]),
            'departments' => $departments,
            'agents' => $agents->map(fn($agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'department' => $agent->department?->name,
                'status' => $agent->status ?? 'online',
                'is_admin' => $agent->is_admin
            ]),
            'emailSources' => EmailSetting::where('enabled', true)
                ->select('id', 'email', 'department_id')
                ->withCount(['conversations as unread_count' => function($query) {
                    $query->where('status', 'new');
                }])
                ->get()
                ->map(fn($source) => [
                    'id' => $source->id,
                    'label' => $source->email,
                    'count' => $source->unread_count,
                    'department_id' => $source->department_id
                ]),
            // Add signatures to the response
            'signatures' => EmailSignature::select(['id', 'name', 'content', 'is_default'])
                ->orderBy('is_default', 'desc')
                ->get()
        ];

        Log::info('Final response data counts:', [
            'departments' => count($response['departments']),
            'agents' => count($response['agents'])
        ]);

        return Inertia::render('Helpdesk/Support', $response);
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 1) . ' ' . $units[$pow];
    }

    public function getSupportUsers()
    {
        $users = SupportUser::query()
            ->withCount('conversations')
            ->with(['conversations' => function($query) {
                $query->latest();
            }])
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tags' => $user->tags ?? [],
                    'lastContact' => $user->last_seen_at?->format('Y-m-d'),
                    'totalConversations' => $user->conversations_count,
                    'status' => $user->last_seen_at?->gt(now()->subDays(30)) ? 'active' : 'inactive'
                ];
            });

        return response()->json($users);
    }

    public function deleteUser(SupportUser $user, Request $request)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized. Only admins can delete users.'], 403);
        }

        try {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete user'], 500);
        }
    }

    public function editUser(SupportUser $user)
    {
        return Inertia::render('Helpdesk/EditUser', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tags' => $user->tags ?? [],
                'company' => $user->company,
                'phone' => $user->phone,
                'location' => $user->location,
                'timezone' => $user->timezone,
                'notes' => $user->notes
            ]
        ]);
    }

    public function updateUser(Request $request, SupportUser $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:support_users,email,' . $user->id,
            'tags' => 'nullable|array',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ]);

        $user->update($validated);
        return redirect()->route('helpdesk.users')->with('success', 'User updated successfully');
    }

    public function getSpamContacts()
    {
        $spamContacts = SpamContact::latest()
            ->get()
            ->map(fn($contact) => [
                'id' => $contact->id,
                'type' => $contact->type,
                'value' => $contact->value,
                'reason' => $contact->reason,
                'last_attempt' => $contact->last_attempt_at?->diffForHumans(),
                'attempts' => $contact->attempt_count,
                'added_at' => $contact->created_at->format('Y-m-d H:i')
            ]);

        return response()->json($spamContacts);
    }

    public function deleteSpamContact(SpamContact $spamContact)
    {
        try {
            DB::transaction(function () use ($spamContact) {
                // Update associated conversations first
                if ($spamContact->type === 'email') {
                    Conversation::where('from_email', $spamContact->value)
                        ->where('status', 'spam')
                        ->update([
                            'status' => 'new',
                            'updated_at' => now()
                        ]);
                }

                // Then delete the spam contact
                $spamContact->delete();
            });

            return response()->json([
                'message' => 'Contact removed from spam list and conversations updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove contact from spam list'
            ], 500);
        }
    }

    public function addSpamContact(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,phone',
            'value' => 'required|string|max:255',
            'reason' => 'nullable|string'
        ]);

        SpamContact::create($validated);
        return response()->json(['message' => 'Contact added to spam list']);
    }
}