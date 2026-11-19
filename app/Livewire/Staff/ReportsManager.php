<?php

namespace App\Livewire\Staff;

use App\Models\Budget;
use App\Models\FinancialEntry;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportsManager extends Component
{
    public string $activeTab = 'financial'; // financial | risk | indicators | budget | operational | audit
    public string $period = '';
    public string $startDate = '';
    public string $endDate = '';

    // Financial Entry modal
    public bool $showEntryModal = false;
    public string $entryType = 'expense';
    public string $entryCategory = '';
    public string $entryAmount = '';
    public string $entryDate = '';
    public string $entryDescription = '';

    // Budget modal
    public bool $showBudgetModal = false;
    public string $budgetPeriod = '';
    public string $budgetCategory = 'Disbursements';
    public string $budgetAmount = '';
    public string $budgetNotes = '';

    public string $flashMessage = '';

    public function mount(): void
    {
        $this->period = now()->format('Y-m');
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        $this->entryDate = now()->toDateString();
        $this->budgetPeriod = $this->period;
    }

    public function updatedPeriod(string $value): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            $dt = \Carbon\Carbon::createFromFormat('Y-m', $value);
            $this->startDate = $dt->copy()->startOfMonth()->toDateString();
            $this->endDate = $dt->copy()->endOfMonth()->toDateString();
            $this->budgetPeriod = $value;
        }
    }

    public function openEntryModal(): void
    {
        abort_unless(Auth::user()?->can('reports.manage'), 403);
        $this->reset(['entryCategory', 'entryAmount', 'entryDescription']);
        $this->entryType = 'expense';
        $this->entryDate = now()->toDateString();
        $this->showEntryModal = true;
    }

    public function saveEntry(): void
    {
        abort_unless(Auth::user()?->can('reports.manage'), 403);
        $allowedTypes = implode(',', array_keys(FinancialEntry::TYPES));
        $this->validate([
            'entryType' => "required|in:{$allowedTypes}",
            'entryCategory' => 'required|string|min:2|max:100',
            'entryAmount' => 'required|numeric|min:1',
            'entryDate' => 'required|date',
            'entryDescription' => 'nullable|string|max:255',
        ]);

        $entry = FinancialEntry::create([
            'type' => $this->entryType,
            'category' => trim($this->entryCategory),
            'amount' => (float) $this->entryAmount,
            'entry_date' => $this->entryDate,
            'description' => trim($this->entryDescription) ?: null,
            'recorded_by' => Auth::id(),
        ]);

        AuditService::log(Auth::user(), 'RECORD_FINANCIAL_ENTRY', $entry->category, "{$entry->type}: TZS " . number_format($entry->amount, 0));
        $this->showEntryModal = false;
        $this->flashMessage = 'Financial entry recorded.';
    }

    public function openBudgetModal(): void
    {
        abort_unless(Auth::user()?->can('reports.manage'), 403);
        $this->budgetPeriod = $this->period;
        $this->budgetCategory = Budget::CATEGORIES[0];
        $this->budgetAmount = '';
        $this->budgetNotes = '';
        $this->showBudgetModal = true;
    }

    public function saveBudget(): void
    {
        abort_unless(Auth::user()?->can('reports.manage'), 403);
        $allowedCategories = implode(',', Budget::CATEGORIES);
        $this->validate([
            'budgetPeriod' => 'required|regex:/^\d{4}-\d{2}$/',
            'budgetCategory' => "required|in:{$allowedCategories}",
            'budgetAmount' => 'required|numeric|min:0',
            'budgetNotes' => 'nullable|string|max:255',
        ]);

        Budget::updateOrCreate(
            ['period' => $this->budgetPeriod, 'category' => $this->budgetCategory],
            [
                'budgeted_amount' => (float) $this->budgetAmount,
                'notes' => trim($this->budgetNotes) ?: null,
                'created_by' => Auth::id(),
            ]
        );

        AuditService::log(Auth::user(), 'SAVE_BUDGET', "{$this->budgetPeriod} - {$this->budgetCategory}", 'TZS ' . number_format((float) $this->budgetAmount, 0));
        $this->showBudgetModal = false;
        $this->flashMessage = 'Budget target saved.';
    }

    public function render(ReportService $reports)
    {
        $reportData = $reports->buildAll($this->startDate, $this->endDate, $this->period);

        return view('livewire.staff.reports-manager', [
            'report' => $reportData,
            'entryTypes' => FinancialEntry::TYPES,
            'budgetCategories' => Budget::CATEGORIES,
            'recentEntries' => FinancialEntry::latest('entry_date')->take(15)->get(),
        ]);
    }
}
