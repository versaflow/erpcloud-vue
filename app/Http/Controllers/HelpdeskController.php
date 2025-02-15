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
use App\Enums\ConversationStatus;
use Illuminate\Http\Request;
use App\Models\SmtpSetting; // Add this import

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
            'emailSettings' => EmailSetting::with(['smtpSetting', 'signatures'])
                ->get()
                ->map(function ($setting) {
                    return [
                        'id' => $setting->id,
                        'email' => $setting->email,
                        'host' => $setting->host,
                        'port' => $setting->port,
                        'username' => $setting->username,
                        'password' => $setting->password,
                        'enabled' => $setting->enabled,
                        'department_id' => $setting->department_id,
                        'imap_settings' => $setting->imap_settings,
                        'smtp_setting' => $setting->smtpSetting ? [
                            'from_name' => $setting->smtpSetting->from_name,
                            'email' => $setting->smtpSetting->email,
                            'host' => $setting->smtpSetting->host,
                            'port' => $setting->smtpSetting->port,
                            'username' => $setting->smtpSetting->username,
                            'password' => $setting->smtpSetting->password,
                            'encryption' => $setting->smtpSetting->encryption,
                        ] : null,
                        'signatures' => $setting->signatures->map(function ($sig) {
                            return [
                                'name' => $sig->name,
                                'content' => $sig->content,
                                'isDefault' => $sig->is_default,
                            ];
                        })
                    ];
                })
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
            'imap_configs.*.username' => 'required',
            'imap_configs.*.password' => 'required',
            'imap_configs.*.enabled' => 'boolean',
            'imap_configs.*.department_id' => 'nullable|exists:departments,id',
            'imap_configs.*.imap_settings' => 'required|array',
            'imap_configs.*.imap_settings.encryption' => 'required|string|in:ssl,tls,none',
            'imap_configs.*.imap_settings.validate_cert' => 'required|boolean',
            'imap_configs.*.smtp_config' => 'required|array',
            'imap_configs.*.smtp_config.from_name' => 'required|string',
            'imap_configs.*.smtp_config.email' => 'required|email',
            'imap_configs.*.smtp_config.host' => 'required|string',
            'imap_configs.*.smtp_config.port' => 'required|numeric',
            'imap_configs.*.smtp_config.username' => 'required|string',
            'imap_configs.*.smtp_config.password' => 'required|string',
            'imap_configs.*.smtp_config.encryption' => 'required|string',
            'imap_configs.*.signatures' => 'array',
            'imap_configs.*.signatures.*.name' => 'required|string',
            'imap_configs.*.signatures.*.content' => 'required|string',
            'imap_configs.*.signatures.*.isDefault' => 'boolean'
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['imap_configs'] as $config) {
                // First save the email setting to get an ID
                $emailSetting = EmailSetting::updateOrCreate(
                    ['email' => $config['email']],
                    [
                        'host' => $config['host'],
                        'port' => $config['port'],
                        'username' => $config['username'],
                        'password' => $config['password'],
                        'enabled' => $config['enabled'] ?? true,
                        'department_id' => $config['department_id'],
                        'imap_settings' => json_encode($config['imap_settings'])
                    ]
                );

                // Save SMTP config
                SmtpSetting::updateOrCreate(
                    ['email_setting_id' => $emailSetting->id],
                    [
                        'from_name' => $config['smtp_config']['from_name'],
                        'email' => $config['smtp_config']['email'],
                        'host' => $config['smtp_config']['host'],
                        'port' => $config['smtp_config']['port'],
                        'username' => $config['smtp_config']['username'],
                        'password' => $config['smtp_config']['password'],
                        'encryption' => $config['smtp_config']['encryption']
                    ]
                );

                // Handle signatures - Modified this part
                if (isset($config['signatures']) && is_array($config['signatures'])) {
                    // Delete existing signatures
                    $emailSetting->signatures()->delete();

                    // Create new signatures
                    foreach ($config['signatures'] as $signature) {
                        $emailSetting->signatures()->create([
                            'name' => $signature['name'],
                            'content' => $signature['content'],
                            'is_default' => $signature['isDefault'] ?? false
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Settings saved successfully',
            'imap_accounts' => EmailSetting::with(['smtpSetting', 'signatures'])->get()
        ]);
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
                'supportUser',
                'department',
                'messages.attachments'
            ])
            ->withCount(['messages' => function($query) {
                $query->where('status', 'unread');
            }])
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
                ]),
                'unread_messages_count' => $conversation->messages_count,
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

        try {
            DB::transaction(function () use ($validated) {
                // Create spam contact
                SpamContact::create($validated);

                // Update any conversations from this email to spam status
                if ($validated['type'] === 'email') {
                    Conversation::where('from_email', $validated['value'])
                        ->update([
                            'status' => 'spam',
                            'updated_at' => now()
                        ]);
                }
            });

            return response()->json([
                'message' => 'Contact added to spam list and conversations updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add contact to spam list'
            ], 500);
        }
    }

    public function assignDepartment(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $conversation->update([
            'department_id' => $validated['department_id']
        ]);

        return response()->json([
            'message' => 'Department assigned successfully',
            'conversation' => $conversation
        ]);
    }

    public function assignAgent(Request $request, Conversation $conversation)

    {
        $validated = $request->validate([
            'agent_id' => 'nullable|sometimes|exists:users,id'
        ]);

        try {
            DB::transaction(function () use ($conversation, $validated) {
                $conversation->update([
                    'agent_id' => $validated['agent_id'],
                    //TODO:undate status to assigned
                    // 'status' => ConversationStatus::ASSIGNED
                ]);
            });

            return response()->json([
                'message' => 'Agent assigned successfully',
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to assign agent'
            ], 500);
        }
    }

    public function archiveConversation(Conversation $conversation)
    {
        try {
            $conversation->update([
                'status' => ConversationStatus::CLOSED,  // Changed from 'archived' to CLOSED enum
                'archived_at' => now()
            ]);

            return response()->json([
                'message' => 'Conversation archived successfully',
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to archive conversation'
            ], 500);
        }
    }

    public function unmarkSpam(Conversation $conversation)
    {
        try {
            DB::transaction(function () use ($conversation) {
                // Remove from spam contacts if exists
                SpamContact::where('type', 'email')
                    ->where('value', $conversation->from_email)
                    ->delete();

                // Update conversation status
                $conversation->update([
                    'status' => ConversationStatus::NEW,
                    'updated_at' => now()
                ]);
            });

            return response()->json([
                'message' => 'Conversation removed from spam',
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove conversation from spam'
            ], 500);
        }
    }

    public function unarchiveConversation(Conversation $conversation)
    {
        try {
            $conversation->update([
                'status' => ConversationStatus::NEW,
                'archived_at' => null
            ]);

            return response()->json([
                'message' => 'Conversation unarchived successfully',
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to unarchive conversation'
            ], 500);
        }
    }

    public function updateStatus(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', ConversationStatus::values())
        ]);

        $conversation->update([
            'status' => $validated['status']
        ]);

        return response()->json([
            'message' => 'Conversation status updated successfully',
            'conversation' => $conversation
        ]);
    }

    public function markMessagesRead(Conversation $conversation)
    {
        try {
            $conversation->messages()
                ->where('status', 'unread')
                ->update([
                    'status' => 'read',
                    'read_at' => now()
                ]);

            return response()->json([
                'message' => 'Messages marked as read',
                'conversation' => $conversation->load('messages')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark messages as read'
            ], 500);
        }
    }
}