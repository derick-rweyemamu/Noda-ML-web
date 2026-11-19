<?php

namespace App\Livewire\Staff;

use App\Models\CommunicationTemplate;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TemplatesManager extends Component
{
    public const CORE_KEYS = ['welcome', 'next_payment', 'overdue', 'payment', 'full_payment', 'contact_marketing'];

    public string $categoryFilter = 'all';
    public string $viewLocale = 'en';

    public bool $showModal = false;
    public ?int $editTemplateId = null;
    public string $editorLocale = 'en';
    public string $name = '';
    public string $category = 'custom';
    public string $emailSubject = '';
    public string $emailSubjectSw = '';
    public string $emailBody = '';
    public string $emailBodySw = '';
    public string $smsBody = '';
    public string $smsBodySw = '';
    public bool $isActive = true;

    public string $flashMessage = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        $this->viewLocale = app()->getLocale() === 'sw' ? 'sw' : 'en';
    }

    public function openCreateModal(): void
    {
        abort_unless(Auth::user()?->can('templates.manage'), 403);
        $this->reset([
            'editTemplateId', 'name',
            'emailSubject', 'emailSubjectSw',
            'emailBody', 'emailBodySw',
            'smsBody', 'smsBodySw',
            'errorMessage',
        ]);
        $this->category = 'custom';
        $this->editorLocale = $this->viewLocale;
        $this->isActive = true;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        abort_unless(Auth::user()?->can('templates.manage'), 403);
        $tpl = CommunicationTemplate::findOrFail($id);
        $this->editTemplateId = $tpl->id;
        $this->name = $tpl->name;
        $this->category = $tpl->category;
        $this->emailSubject = $tpl->email_subject ?? '';
        $this->emailSubjectSw = $tpl->email_subject_sw ?? '';
        $this->emailBody = $tpl->email_body ?? '';
        $this->emailBodySw = $tpl->email_body_sw ?? '';
        $this->smsBody = $tpl->sms_body ?? '';
        $this->smsBodySw = $tpl->sms_body_sw ?? '';
        $this->isActive = $tpl->is_active;
        $this->editorLocale = $this->viewLocale;
        $this->errorMessage = '';
        $this->showModal = true;
    }

    public function saveTemplate(): void
    {
        abort_unless(Auth::user()?->can('templates.manage'), 403);
        $allowedCategories = implode(',', array_keys(CommunicationTemplate::CATEGORIES));
        $this->validate([
            'name' => 'required|string|min:2|max:120',
            'category' => "required|in:{$allowedCategories}",
            'emailSubject' => 'nullable|string|max:200',
            'emailSubjectSw' => 'nullable|string|max:200',
            'emailBody' => 'nullable|string',
            'emailBodySw' => 'nullable|string',
            'smsBody' => 'nullable|string|max:1000',
            'smsBodySw' => 'nullable|string|max:1000',
            'isActive' => 'boolean',
        ]);

        $payload = [
            'name' => trim($this->name),
            'category' => $this->category,
            'email_subject' => trim($this->emailSubject) ?: null,
            'email_subject_sw' => trim($this->emailSubjectSw) ?: null,
            'email_body' => trim($this->emailBody) ?: null,
            'email_body_sw' => trim($this->emailBodySw) ?: null,
            'sms_body' => trim($this->smsBody) ?: null,
            'sms_body_sw' => trim($this->smsBodySw) ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editTemplateId) {
            $tpl = CommunicationTemplate::findOrFail($this->editTemplateId);
            $tpl->update($payload);
            AuditService::log(Auth::user(), 'UPDATE_TEMPLATE', $tpl->name, "Updated template ({$tpl->category})");
            $this->flashMessage = "Template '{$tpl->name}' updated.";
        } else {
            $baseKey = Str::slug($payload['name'], '_');
            $key = $baseKey ?: 'tpl';
            $suffix = 1;
            while (CommunicationTemplate::where('key', $key)->exists()) {
                $key = ($baseKey ?: 'tpl') . '_' . (++$suffix);
            }
            $tpl = CommunicationTemplate::create(array_merge($payload, ['key' => $key]));
            AuditService::log(Auth::user(), 'CREATE_TEMPLATE', $tpl->name, "Created template ({$tpl->category})");
            $this->flashMessage = "Template '{$tpl->name}' created.";
        }

        $this->showModal = false;
        $this->reset([
            'editTemplateId', 'name',
            'emailSubject', 'emailSubjectSw',
            'emailBody', 'emailBodySw',
            'smsBody', 'smsBodySw',
        ]);
    }

    public function toggleActive(int $id): void
    {
        abort_unless(Auth::user()?->can('templates.manage'), 403);
        $tpl = CommunicationTemplate::findOrFail($id);
        $tpl->update(['is_active' => ! $tpl->is_active]);
        $this->flashMessage = "Template '{$tpl->name}' " . ($tpl->is_active ? 'activated' : 'paused') . '.';
    }

    public function deleteTemplate(int $id): void
    {
        abort_unless(Auth::user()?->can('templates.manage'), 403);
        $tpl = CommunicationTemplate::findOrFail($id);
        if (in_array($tpl->key, self::CORE_KEYS, true)) {
            $this->errorMessage = "Core system template '{$tpl->name}' cannot be deleted.";
            return;
        }
        $name = $tpl->name;
        $tpl->delete();
        AuditService::log(Auth::user(), 'DELETE_TEMPLATE', $name, 'Deleted custom communication template');
        $this->flashMessage = "Template '{$name}' deleted.";
    }

    public function render()
    {
        $templates = CommunicationTemplate::query()
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('id')
            ->get();

        $activeSubject = $this->editorLocale === 'sw' ? ($this->emailSubjectSw ?: $this->emailSubject) : $this->emailSubject;
        $activeBody = $this->editorLocale === 'sw' ? ($this->emailBodySw ?: $this->emailBody) : $this->emailBody;
        $activeSms = $this->editorLocale === 'sw' ? ($this->smsBodySw ?: $this->smsBody) : $this->smsBody;

        return view('livewire.staff.templates-manager', [
            'templates' => $templates,
            'categories' => CommunicationTemplate::CATEGORIES,
            'placeholders' => CommunicationTemplate::PLACEHOLDERS,
            'coreKeys' => self::CORE_KEYS,
            'previewEmailSubject' => CommunicationTemplate::renderText($activeSubject),
            'previewEmailBody' => CommunicationTemplate::renderText($activeBody),
            'previewSmsBody' => CommunicationTemplate::renderText($activeSms),
        ]);
    }
}
