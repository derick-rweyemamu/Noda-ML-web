<?php

namespace App\Livewire\Staff;

use App\Models\CommunicationTemplate;
use App\Models\Country;
use App\Models\Group;
use App\Models\Member;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\PhoneHelper;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ContactsManager extends Component
{
    public string $search = '';
    public string $groupFilter = '';

    public bool $showModal = false;
    public ?int $editMemberId = null;
    public ?int $groupId = null;
    public string $fullName = '';
    public string $phoneCode = '+255';
    public string $phone = '';
    public string $email = '';

    // Send Message / Marketing modal
    public array $selectedContactIds = [];
    public bool $showMessageModal = false;
    public ?int $selectedTemplateId = null;
    public string $messageChannel = 'sms'; // sms | email | both
    public string $messageLocale = 'en';
    public string $messageEmailSubject = '';
    public string $messageEmailBody = '';
    public string $messageSmsBody = '';

    public string $flashMessage = '';
    public string $errorMessage = '';

    public function openCreateModal(): void
    {
        abort_unless(Auth::user()?->can('members.add'), 403);
        $this->reset(['editMemberId', 'fullName', 'phone', 'email']);
        $this->phoneCode = '+255';
        $this->groupId = Group::orderBy('name')->value('id');
        $this->showModal = true;
    }

    public function openEditModal(int $memberId): void
    {
        abort_unless(Auth::user()?->can('members.edit'), 403);
        $member = Member::findOrFail($memberId);
        [$code, $local] = PhoneHelper::splitE164($member->phone);

        $this->editMemberId = $member->id;
        $this->groupId = $member->group_id;
        $this->fullName = $member->full_name;
        $this->phoneCode = $code;
        $this->phone = $local;
        $this->email = $member->email ?? '';
        $this->showModal = true;
    }

    public function saveContact(): void
    {
        if ($this->editMemberId) {
            abort_unless(Auth::user()?->can('members.edit'), 403);
        } else {
            abort_unless(Auth::user()?->can('members.add'), 403);
        }

        $fullPhone = PhoneHelper::formatE164($this->phoneCode, $this->phone);
        $this->phone = $fullPhone !== '' ? ltrim(substr($fullPhone, strlen($this->phoneCode))) : '';

        $uniquePhoneRule = 'required|string|min:8|unique:members,phone' . ($this->editMemberId ? ',' . $this->editMemberId : '');
        $validated = validator([
            'groupId' => $this->groupId,
            'fullName' => $this->fullName,
            'phone' => $fullPhone,
            'email' => trim($this->email) ?: null,
        ], [
            'groupId' => 'required|exists:groups,id',
            'fullName' => 'required|string|min:2',
            'phone' => $uniquePhoneRule,
            'email' => 'nullable|email',
        ])->validate();

        $payload = [
            'group_id' => $validated['groupId'],
            'full_name' => trim($validated['fullName']),
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ];

        if ($this->editMemberId) {
            $member = Member::findOrFail($this->editMemberId);
            $member->update($payload);
            AuditService::log(Auth::user(), 'UPDATE_CONTACT', $member->full_name, "Updated contact ({$member->phone})");
            $this->flashMessage = "{$member->full_name} updated.";
        } else {
            $member = Member::create($payload);
            AuditService::log(Auth::user(), 'CREATE_CONTACT', $member->full_name, "Added contact ({$member->phone})");
            $this->flashMessage = "{$member->full_name} added to contacts.";
        }

        $this->showModal = false;
        $this->reset(['editMemberId', 'groupId', 'fullName', 'phone', 'email']);
        $this->phoneCode = '+255';
    }

    public function openMessageModal(?int $singleMemberId = null): void
    {
        abort_unless(Auth::user()?->can('notifications.dispatch'), 403);
        $this->errorMessage = '';

        if ($singleMemberId !== null) {
            $this->selectedContactIds = [$singleMemberId];
        } elseif (empty($this->selectedContactIds)) {
            $this->selectedContactIds = $this->filteredContactsQuery()->pluck('id')->all();
        }

        if (empty($this->selectedContactIds)) {
            $this->errorMessage = 'No contacts selected to message.';
            return;
        }

        $this->messageLocale = app()->getLocale() === 'sw' ? 'sw' : 'en';
        $defaultTpl = CommunicationTemplate::where('key', 'contact_marketing')->where('is_active', true)->first()
            ?? CommunicationTemplate::where('is_active', true)->orderBy('id')->first();

        $this->selectedTemplateId = $defaultTpl?->id;
        $this->applySelectedTemplate();
        $this->showMessageModal = true;
    }

    public function updatedSelectedTemplateId(): void
    {
        $this->applySelectedTemplate();
    }

    public function updatedMessageLocale(): void
    {
        $this->applySelectedTemplate();
    }

    private function applySelectedTemplate(): void
    {
        $tpl = $this->selectedTemplateId ? CommunicationTemplate::find($this->selectedTemplateId) : null;
        if (! $tpl) {
            return;
        }
        $localized = $tpl->forLocale($this->messageLocale);
        $this->messageEmailSubject = $localized['email_subject'] ?? '';
        $this->messageEmailBody = $localized['email_body'] ?? '';
        $this->messageSmsBody = $localized['sms_body'] ?? '';
    }

    public function sendMessages(NotificationService $notifications): void
    {
        abort_unless(Auth::user()?->can('notifications.dispatch'), 403);
        $this->errorMessage = '';

        $rules = [
            'messageChannel' => 'required|in:sms,email,both',
        ];
        if (in_array($this->messageChannel, ['sms', 'both'], true)) {
            $rules['messageSmsBody'] = 'required|string|min:2|max:1000';
        }
        if (in_array($this->messageChannel, ['email', 'both'], true)) {
            $rules['messageEmailSubject'] = 'required|string|min:2|max:200';
            $rules['messageEmailBody'] = 'required|string|min:2';
        }
        $this->validate($rules);

        $contacts = Member::with('group')->whereIn('id', $this->selectedContactIds)->get();
        if ($contacts->isEmpty()) {
            $this->errorMessage = 'No valid contacts selected.';
            return;
        }

        $smsSent = 0;
        $emailSent = 0;

        foreach ($contacts as $contact) {
            $vars = [
                '{name}' => $contact->full_name,
                '{group}' => $contact->group?->name ?? 'NODA ML',
                '{company}' => 'NODA ML',
            ];

            if (in_array($this->messageChannel, ['sms', 'both'], true) && ! empty($contact->phone)) {
                $smsText = CommunicationTemplate::renderText($this->messageSmsBody, $vars);
                if ($notifications->sendSms($contact->phone, $smsText)) {
                    $smsSent++;
                }
            }

            if (in_array($this->messageChannel, ['email', 'both'], true) && ! empty($contact->email)) {
                $subj = CommunicationTemplate::renderText($this->messageEmailSubject, $vars);
                $body = CommunicationTemplate::renderText($this->messageEmailBody, $vars);
                if ($notifications->sendEmail($contact->email, $subj, $body)) {
                    $emailSent++;
                }
            }
        }

        AuditService::log(
            Auth::user(),
            'SEND_CONTACT_MESSAGE',
            $contacts->count() . ' contact(s)',
            "Channel={$this->messageChannel}, SMS={$smsSent}, Email={$emailSent}"
        );

        $this->showMessageModal = false;
        $this->selectedContactIds = [];
        $this->flashMessage = "Sent {$smsSent} SMS and {$emailSent} Email message(s).";
    }

    private function filteredContactsQuery()
    {
        return Member::with('group')
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('full_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->groupFilter !== '', fn ($q) => $q->where('group_id', $this->groupFilter))
            ->orderBy('full_name');
    }

    public function render()
    {
        $contacts = $this->filteredContactsQuery()->get();

        return view('livewire.staff.contacts-manager', [
            'contacts' => $contacts,
            'groups' => Group::orderBy('name')->get(),
            'templates' => CommunicationTemplate::where('is_active', true)->orderBy('id')->get(),
            'countries' => Country::orderByRaw("CASE WHEN iso2 = 'TZ' THEN 0 ELSE 1 END, name ASC")->get(),
        ]);
    }
}
