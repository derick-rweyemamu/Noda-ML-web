<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\FinancialEntry;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        private readonly LoanCalculatorService $calc
    ) {}

    public function buildAll(?string $startDate = null, ?string $endDate = null, ?string $period = null): array
    {
        $now = now();
        $period = $period ?: $now->format('Y-m');
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::createFromFormat('Y-m', $period)->endOfMonth();
        $settings = AdminSetting::current();

        $groups = Group::with(['loanOfficer.staffProfile', 'members.referees', 'members.collaterals', 'loans.payments'])->get();
        $members = $groups->flatMap->members;
        $loans = $groups->flatMap->loans;
        $allPayments = Payment::all();

        $periodLoans = $loans->filter(fn (Loan $l) => $l->start_date && $l->start_date->between($start, $end));
        $periodPayments = $allPayments->filter(fn (Payment $p) => $p->paid_at && $p->paid_at->between($start, $end));
        $periodPenalties = Penalty::whereBetween('applied_at', [$start, $end])->get();
        $entries = FinancialEntry::whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])->get();
        $allEntries = FinancialEntry::all();

        // Compute live loan metrics
        $grossPortfolioOutstanding = 0.0;
        $overduePortfolio = 0.0;
        $activeCount = 0;
        $overdueCount = 0;
        $completedCount = 0;
        $expectedNext7Days = 0.0;
        $expectedNext30Days = 0.0;

        $parBuckets = [
            '1_30' => ['label' => '1–30 Days', 'count' => 0, 'amount' => 0.0],
            '31_60' => ['label' => '31–60 Days', 'count' => 0, 'amount' => 0.0],
            '61_90' => ['label' => '61–90 Days', 'count' => 0, 'amount' => 0.0],
            '90_plus' => ['label' => '90+ Days', 'count' => 0, 'amount' => 0.0],
        ];

        $totalInterestExpected = 0.0;
        $totalInterestEarned = 0.0;

        foreach ($loans as $loan) {
            $m = $this->calc->computeMetrics($loan, $now, $settings);
            $interestTotal = max(0.0, (float) $loan->total_repayable - (float) $loan->principal);
            $totalInterestExpected += $interestTotal;
            $totalInterestEarned += $interestTotal * $m['progress'];

            if ($loan->status === 'completed' || $m['remaining_loan_balance'] <= 0.01) {
                $completedCount++;
                continue;
            }

            $activeCount++;
            $grossPortfolioOutstanding += $m['remaining_loan_balance'];

            if ($m['is_overdue']) {
                $overdueCount++;
                $overduePortfolio += $m['remaining_loan_balance'];

                $due = $m['next_payment_due_date'];
                $daysLate = $due ? max(1, (int) $due->diffInDays($now)) : 1;
                if ($daysLate <= 30) {
                    $parBuckets['1_30']['count']++;
                    $parBuckets['1_30']['amount'] += $m['remaining_loan_balance'];
                } elseif ($daysLate <= 60) {
                    $parBuckets['31_60']['count']++;
                    $parBuckets['31_60']['amount'] += $m['remaining_loan_balance'];
                } elseif ($daysLate <= 90) {
                    $parBuckets['61_90']['count']++;
                    $parBuckets['61_90']['amount'] += $m['remaining_loan_balance'];
                } else {
                    $parBuckets['90_plus']['count']++;
                    $parBuckets['90_plus']['amount'] += $m['remaining_loan_balance'];
                }
            }

            if ($m['next_payment_due_date']) {
                $daysUntil = $now->diffInDays($m['next_payment_due_date'], false);
                if ($daysUntil >= 0 && $daysUntil <= 7) {
                    $expectedNext7Days += $m['amount_due_this_week'];
                }
                if ($daysUntil >= 0 && $daysUntil <= 30) {
                    $expectedNext30Days += $m['weekly_amount'] ?? $loan->weekly_amount;
                }
            }
        }

        // 1. Financial Statements
        $periodDisbursed = (float) $periodLoans->sum('principal');
        $periodCollected = (float) $periodPayments->sum('amount');
        $periodInterestIncome = (float) $periodLoans->sum(fn (Loan $l) => max(0.0, (float) $l->total_repayable - (float) $l->principal));
        $periodPenaltyIncome = (float) $periodPenalties->sum('total_penalty');
        $periodOtherIncome = (float) $entries->where('type', 'other_income')->sum('amount');
        $periodExpenses = (float) $entries->where('type', 'expense')->sum('amount');
        $totalRevenue = $this->calc->round2($periodInterestIncome + $periodPenaltyIncome + $periodOtherIncome);
        $netIncome = $this->calc->round2($totalRevenue - $periodExpenses);

        $allDisbursed = (float) $loans->sum('principal');
        $allCollected = (float) $allPayments->sum('amount');
        $allPenaltyReceivables = (float) $groups->sum('penalty_balance');
        $recordedAssets = (float) $allEntries->where('type', 'asset')->sum('amount');
        $recordedLiabilities = (float) $allEntries->where('type', 'liability')->sum('amount');
        $recordedEquity = (float) $allEntries->where('type', 'equity')->sum('amount');
        $allExpenses = (float) $allEntries->where('type', 'expense')->sum('amount');
        $allOtherIncome = (float) $allEntries->where('type', 'other_income')->sum('amount');

        $cashAndBank = $this->calc->round2($recordedAssets + $allCollected - $allDisbursed - $allExpenses + $allOtherIncome);
        $totalAssets = $this->calc->round2($cashAndBank + $grossPortfolioOutstanding + $allPenaltyReceivables);
        $retainedEarnings = $this->calc->round2($totalInterestEarned + (float) Penalty::sum('total_penalty') + $allOtherIncome - $allExpenses);
        $totalEquityAndLiabilities = $this->calc->round2($recordedLiabilities + $recordedEquity + $retainedEarnings);

        $operatingCashFlow = $this->calc->round2($periodCollected - $periodDisbursed - $periodExpenses + $periodOtherIncome);
        $financingCashFlow = $this->calc->round2(
            (float) $entries->where('type', 'equity')->sum('amount') +
            (float) $entries->where('type', 'liability')->sum('amount') -
            (float) $entries->where('type', 'asset')->sum('amount')
        );

        // 2. Portfolio Quality & Risk
        $parRatio = $grossPortfolioOutstanding > 0 ? round(($overduePortfolio / $grossPortfolioOutstanding) * 100, 2) : 0.0;
        $totalCollateralValue = (float) $members->flatMap->collaterals->sum(fn ($c) => (float) ($c->estimated_value ?? 0));
        $collateralCoverage = $grossPortfolioOutstanding > 0 ? round(($totalCollateralValue / $grossPortfolioOutstanding) * 100, 2) : 0.0;

        $groupRisk = $groups->map(function (Group $g) use ($now, $settings) {
            $gOut = 0.0;
            $gOverdue = 0;
            foreach ($g->loans as $l) {
                $m = $this->calc->computeMetrics($l, $now, $settings);
                if ($l->status !== 'completed') {
                    $gOut += $m['remaining_loan_balance'];
                    if ($m['is_overdue']) {
                        $gOverdue++;
                    }
                }
            }
            return [
                'group_id' => $g->id,
                'group_name' => $g->name,
                'loan_officer' => $g->loanOfficer?->name ?? 'Unassigned',
                'members_count' => $g->members->count(),
                'disbursed' => $this->calc->round2((float) $g->loans->sum('principal')),
                'outstanding' => $this->calc->round2($gOut),
                'overdue_loans' => $gOverdue,
                'penalty_balance' => $this->calc->round2((float) $g->penalty_balance),
            ];
        })->sortByDesc('overdue_loans')->values()->all();

        // 3. Financial & Management Indicators
        $officerCount = max(1, User::role('Loan Officer')->count());
        $totalRepayableAll = (float) $loans->sum('total_repayable');
        $collectionRate = $totalRepayableAll > 0 ? round(($allCollected / $totalRepayableAll) * 100, 2) : 0.0;
        $portfolioYield = $allDisbursed > 0 ? round(($totalInterestEarned / $allDisbursed) * 100, 2) : 0.0;
        $operatingExpenseRatio = $grossPortfolioOutstanding > 0 ? round(($periodExpenses / $grossPortfolioOutstanding) * 100, 2) : 0.0;
        $ossRatio = $periodExpenses > 0 ? round(($totalRevenue / $periodExpenses) * 100, 2) : ($totalRevenue > 0 ? 100.0 : 0.0);
        $avgLoanSize = $loans->count() > 0 ? $this->calc->round2($allDisbursed / $loans->count()) : 0.0;
        $portfolioPerOfficer = $this->calc->round2($grossPortfolioOutstanding / $officerCount);

        // 4. Budget vs. Actual
        $budgets = Budget::where('period', $period)->get()->keyBy('category');
        $actualByCategory = [
            'Disbursements' => $periodDisbursed,
            'Collections' => $periodCollected,
            'Interest & Fee Income' => $totalRevenue,
            'Operating Expenses' => $periodExpenses,
        ];
        $budgetVsActual = collect(Budget::CATEGORIES)->map(function (string $cat) use ($budgets, $actualByCategory) {
            $budgeted = (float) ($budgets->get($cat)?->budgeted_amount ?? 0.0);
            $actual = $this->calc->round2((float) ($actualByCategory[$cat] ?? 0.0));
            $variance = $this->calc->round2($actual - $budgeted);
            $pct = $budgeted > 0 ? round(($actual / $budgeted) * 100, 1) : 0.0;
            return [
                'category' => $cat,
                'budgeted' => $budgeted,
                'actual' => $actual,
                'variance' => $variance,
                'achievement_pct' => $pct,
            ];
        })->all();

        // 5. Operational Summary & Staff Productivity
        $femaleCount = $members->where('gender', 'female')->count();
        $maleCount = $members->where('gender', 'male')->count();
        $officerProductivity = User::role('Loan Officer')->with('staffProfile')->get()->map(function (User $officer) use ($groups, $now, $settings) {
            $assignedGroups = $groups->where('loan_officer_id', $officer->id);
            $oMembers = $assignedGroups->flatMap->members;
            $oLoans = $assignedGroups->flatMap->loans;
            $oOut = 0.0;
            $oOverdue = 0;
            foreach ($oLoans as $l) {
                $m = $this->calc->computeMetrics($l, $now, $settings);
                if ($l->status !== 'completed') {
                    $oOut += $m['remaining_loan_balance'];
                    if ($m['is_overdue']) {
                        $oOverdue++;
                    }
                }
            }
            return [
                'officer_id' => $officer->id,
                'officer_name' => $officer->name,
                'phone' => $officer->staffProfile?->phone,
                'groups_count' => $assignedGroups->count(),
                'members_count' => $oMembers->count(),
                'disbursed' => $this->calc->round2((float) $oLoans->sum('principal')),
                'outstanding' => $this->calc->round2($oOut),
                'overdue_loans' => $oOverdue,
            ];
        })->values()->all();

        // 6. Audit & Compliance
        $totalMembers = max(1, $members->count());
        $withId = $members->filter(fn (Member $m) => ! empty($m->id_number))->count();
        $withIdImg = $members->filter(fn (Member $m) => ! empty($m->id_image_path))->count();
        $withPhoto = $members->filter(fn (Member $m) => ! empty($m->photo_path))->count();
        $withReferee = $members->filter(fn (Member $m) => $m->referees->isNotEmpty())->count();
        $withCollateral = $members->filter(fn (Member $m) => $m->collaterals->isNotEmpty())->count();

        $mpesaReconciled = MpesaTransaction::where('status', 'reconciled')->count();
        $mpesaUnmatched = MpesaTransaction::where('status', 'unmatched')->count();
        $recentAudits = AuditLog::latest()->take(25)->get()->map(fn (AuditLog $a) => [
            'id' => $a->id,
            'actor_name' => $a->actor_name,
            'actor_role' => $a->actor_role,
            'action' => $a->action,
            'target' => $a->target,
            'details' => $a->details,
            'created_at' => $a->created_at?->format('d M Y H:i'),
        ])->all();

        return [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'period' => $period,
            ],
            'financial_statements' => [
                'income_statement' => [
                    'interest_income' => $this->calc->round2($periodInterestIncome),
                    'penalty_income' => $this->calc->round2($periodPenaltyIncome),
                    'other_income' => $this->calc->round2($periodOtherIncome),
                    'total_revenue' => $totalRevenue,
                    'operating_expenses' => $this->calc->round2($periodExpenses),
                    'net_income' => $netIncome,
                ],
                'balance_sheet' => [
                    'cash_and_bank' => $cashAndBank,
                    'loan_portfolio_outstanding' => $this->calc->round2($grossPortfolioOutstanding),
                    'penalty_receivables' => $this->calc->round2($allPenaltyReceivables),
                    'total_assets' => $totalAssets,
                    'liabilities' => $this->calc->round2($recordedLiabilities),
                    'paid_in_equity' => $this->calc->round2($recordedEquity),
                    'retained_earnings' => $retainedEarnings,
                    'total_liabilities_and_equity' => $totalEquityAndLiabilities,
                ],
                'cash_flow' => [
                    'inflows_collections' => $this->calc->round2($periodCollected),
                    'inflows_other_income' => $this->calc->round2($periodOtherIncome),
                    'outflows_disbursements' => $this->calc->round2($periodDisbursed),
                    'outflows_expenses' => $this->calc->round2($periodExpenses),
                    'net_operating_cash_flow' => $operatingCashFlow,
                    'net_financing_investing' => $financingCashFlow,
                    'net_cash_change' => $this->calc->round2($operatingCashFlow + $financingCashFlow),
                ],
            ],
            'portfolio_risk' => [
                'gross_portfolio' => $this->calc->round2($grossPortfolioOutstanding),
                'overdue_portfolio' => $this->calc->round2($overduePortfolio),
                'par_ratio' => $parRatio,
                'active_loans' => $activeCount,
                'overdue_loans' => $overdueCount,
                'completed_loans' => $completedCount,
                'total_collateral_value' => $this->calc->round2($totalCollateralValue),
                'collateral_coverage_pct' => $collateralCoverage,
                'expected_next_7_days' => $this->calc->round2($expectedNext7Days),
                'expected_next_30_days' => $this->calc->round2($expectedNext30Days),
                'par_aging' => array_values($parBuckets),
                'group_risk' => $groupRisk,
            ],
            'indicators' => [
                'collection_rate_pct' => $collectionRate,
                'portfolio_yield_pct' => $portfolioYield,
                'operating_expense_ratio_pct' => $operatingExpenseRatio,
                'oss_pct' => $ossRatio,
                'average_loan_size' => $avgLoanSize,
                'portfolio_per_officer' => $portfolioPerOfficer,
            ],
            'budget_vs_actual' => $budgetVsActual,
            'operational_summary' => [
                'total_groups' => $groups->count(),
                'total_borrowers' => $members->count(),
                'female_borrowers' => $femaleCount,
                'male_borrowers' => $maleCount,
                'period_disbursements' => $this->calc->round2($periodDisbursed),
                'period_collections' => $this->calc->round2($periodCollected),
                'all_time_disbursements' => $this->calc->round2($allDisbursed),
                'all_time_collections' => $this->calc->round2($allCollected),
                'officer_productivity' => $officerProductivity,
            ],
            'audit_compliance' => [
                'kyc' => [
                    'total_members' => $members->count(),
                    'id_number_pct' => round(($withId / $totalMembers) * 100, 1),
                    'id_image_pct' => round(($withIdImg / $totalMembers) * 100, 1),
                    'photo_pct' => round(($withPhoto / $totalMembers) * 100, 1),
                    'referee_pct' => round(($withReferee / $totalMembers) * 100, 1),
                    'collateral_pct' => round(($withCollateral / $totalMembers) * 100, 1),
                ],
                'mpesa_reconciled' => $mpesaReconciled,
                'mpesa_unmatched' => $mpesaUnmatched,
                'recent_audits' => $recentAudits,
            ],
        ];
    }
}
