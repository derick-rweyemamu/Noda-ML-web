<div class="space-y-5">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Top Filters & Actions --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 flex flex-wrap items-end justify-between gap-3 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">{{ __('messages.month_period') }}</label>
                <input type="month" wire:model.live="period" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">{{ __('messages.from_date') }}</label>
                <input type="date" wire:model.live="startDate" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 mb-1">{{ __('messages.to_date') }}</label>
                <input type="date" wire:model.live="endDate" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
            </div>
        </div>

        @can('reports.manage')
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="openBudgetModal" class="rounded-xl border border-slate-300 dark:border-slate-600 px-3.5 py-2 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700">
                + {{ __('messages.set_budget') }}
            </button>
            <button wire:click="openEntryModal" class="rounded-xl bg-[#B3261E] hover:bg-[#8C1D18] text-white px-4 py-2 text-xs font-bold">
                + {{ __('messages.record_entry') }}
            </button>
        </div>
        @endcan
    </div>

    {{-- Report Module Tabs --}}
    <div class="flex flex-wrap gap-2">
        @php
            $tabs = [
                'financial' => __('messages.tab_financial_statements'),
                'risk' => __('messages.tab_portfolio_risk'),
                'indicators' => __('messages.tab_indicators'),
                'budget' => __('messages.tab_budget_actual'),
                'operational' => __('messages.tab_operational'),
                'audit' => __('messages.tab_audit_compliance'),
            ];
        @endphp
        @foreach($tabs as $k => $label)
            <button wire:click="$set('activeTab', '{{ $k }}')"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $activeTab === $k ? 'bg-[#B3261E] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- 1. Financial Statements --}}
    @if($activeTab === 'financial')
        @php
            $is = $report['financial_statements']['income_statement'];
            $bs = $report['financial_statements']['balance_sheet'];
            $cf = $report['financial_statements']['cash_flow'];
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-2.5 text-xs shadow-sm">
                <h3 class="text-sm font-bold border-b border-slate-200 dark:border-slate-700 pb-2">{{ __('messages.income_statement') }}</h3>
                <div class="flex justify-between"><span>{{ __('messages.interest_income') }}</span><strong>TZS {{ number_format($is['interest_income'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.penalty_income') }}</span><strong>TZS {{ number_format($is['penalty_income'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.other_income') }}</span><strong>TZS {{ number_format($is['other_income'], 0) }}</strong></div>
                <div class="flex justify-between pt-1 border-t border-slate-100 dark:border-slate-700 font-bold"><span>{{ __('messages.total_revenue') }}</span><span>TZS {{ number_format($is['total_revenue'], 0) }}</span></div>
                <div class="flex justify-between text-red-600 dark:text-red-400"><span>{{ __('messages.operating_expenses') }}</span><strong>(TZS {{ number_format($is['operating_expenses'], 0) }})</strong></div>
                <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-700 text-sm font-extrabold {{ $is['net_income'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    <span>{{ __('messages.net_surplus') }}</span>
                    <span>TZS {{ number_format($is['net_income'], 0) }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-2.5 text-xs shadow-sm">
                <h3 class="text-sm font-bold border-b border-slate-200 dark:border-slate-700 pb-2">{{ __('messages.balance_sheet') }}</h3>
                <div class="flex justify-between"><span>{{ __('messages.cash_and_bank') }}</span><strong>TZS {{ number_format($bs['cash_and_bank'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.loan_portfolio_outstanding') }}</span><strong>TZS {{ number_format($bs['loan_portfolio_outstanding'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.penalty_receivables') }}</span><strong>TZS {{ number_format($bs['penalty_receivables'], 0) }}</strong></div>
                <div class="flex justify-between pt-1 border-t border-slate-100 dark:border-slate-700 font-bold"><span>{{ __('messages.total_assets') }}</span><span>TZS {{ number_format($bs['total_assets'], 0) }}</span></div>
                <div class="flex justify-between pt-2"><span>{{ __('messages.liabilities_borrowings') }}</span><strong>TZS {{ number_format($bs['liabilities'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.paid_in_equity') }}</span><strong>TZS {{ number_format($bs['paid_in_equity'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.retained_earnings') }}</span><strong>TZS {{ number_format($bs['retained_earnings'], 0) }}</strong></div>
                <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-700 font-extrabold"><span>{{ __('messages.total_liabilities_equity') }}</span><span>TZS {{ number_format($bs['total_liabilities_and_equity'], 0) }}</span></div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-2.5 text-xs shadow-sm">
                <h3 class="text-sm font-bold border-b border-slate-200 dark:border-slate-700 pb-2">{{ __('messages.cash_flow_statement') }}</h3>
                <div class="flex justify-between"><span>{{ __('messages.collections_inflow') }}</span><strong class="text-emerald-600">+TZS {{ number_format($cf['inflows_collections'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.other_income_inflow') }}</span><strong class="text-emerald-600">+TZS {{ number_format($cf['inflows_other_income'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.disbursements_outflow') }}</span><strong class="text-red-600">-TZS {{ number_format($cf['outflows_disbursements'], 0) }}</strong></div>
                <div class="flex justify-between"><span>{{ __('messages.operating_expenses') }}</span><strong class="text-red-600">-TZS {{ number_format($cf['outflows_expenses'], 0) }}</strong></div>
                <div class="flex justify-between pt-1 border-t border-slate-100 dark:border-slate-700 font-bold"><span>{{ __('messages.net_operating_cash_flow') }}</span><span>TZS {{ number_format($cf['net_operating_cash_flow'], 0) }}</span></div>
                <div class="flex justify-between"><span>{{ __('messages.financing_investing') }}</span><strong>TZS {{ number_format($cf['net_financing_investing'], 0) }}</strong></div>
                <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-700 text-sm font-extrabold"><span>{{ __('messages.net_cash_change') }}</span><span>TZS {{ number_format($cf['net_cash_change'], 0) }}</span></div>
            </div>
        </div>

        @if($recentEntries->isNotEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 shadow-sm">
            <h3 class="text-sm font-bold">{{ __('messages.recent_financial_entries') }}</h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-700/70 text-xs">
                @foreach($recentEntries as $e)
                    <div class="py-2 flex flex-wrap justify-between gap-2">
                        <div>
                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 font-semibold uppercase text-[10px]">{{ $e->type }}</span>
                            <strong class="ml-2">{{ $e->category }}</strong>
                            @if($e->description) <span class="text-slate-500">• {{ $e->description }}</span> @endif
                        </div>
                        <div>
                            <strong>TZS {{ number_format($e->amount, 0) }}</strong>
                            <span class="text-slate-400 ml-2">{{ $e->entry_date?->format('d M Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    {{-- 2. Portfolio Quality & Risk --}}
    @elseif($activeTab === 'risk')
        @php $pr = $report['portfolio_risk']; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.gross_loan_portfolio') }}</div>
                <div class="text-lg font-extrabold mt-1">TZS {{ number_format($pr['gross_portfolio'], 0) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.portfolio_at_risk') }}</div>
                <div class="text-lg font-extrabold text-red-600 mt-1">{{ $pr['par_ratio'] }}%</div>
                <div class="text-[11px] text-slate-500">TZS {{ number_format($pr['overdue_portfolio'], 0) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.collateral_coverage') }}</div>
                <div class="text-lg font-extrabold text-emerald-600 mt-1">{{ $pr['collateral_coverage_pct'] }}%</div>
                <div class="text-[11px] text-slate-500">TZS {{ number_format($pr['total_collateral_value'], 0) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.expected_collections_7_30') }}</div>
                <div class="text-sm font-bold mt-1">TZS {{ number_format($pr['expected_next_7_days'], 0) }}</div>
                <div class="text-[11px] text-slate-500">30d: TZS {{ number_format($pr['expected_next_30_days'], 0) }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 shadow-sm">
            <h3 class="text-sm font-bold">{{ __('messages.par_aging_breakdown') }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                @foreach($pr['par_aging'] as $b)
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3">
                        <div class="text-slate-400">{{ $b['label'] }}</div>
                        <div class="text-base font-bold mt-0.5">{{ $b['count'] }} {{ __('messages.loans') }}</div>
                        <div class="text-[#B3261E] dark:text-[#F2B8B5] font-semibold">TZS {{ number_format($b['amount'], 0) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 shadow-sm overflow-x-auto">
            <h3 class="text-sm font-bold">{{ __('messages.group_risk_breakdown') }}</h3>
            <table class="w-full text-left text-xs">
                <thead class="text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-2">{{ __('messages.select_group') }}</th>
                        <th class="py-2">{{ __('messages.loan_officer') }}</th>
                        <th class="py-2">{{ __('messages.disbursed') }}</th>
                        <th class="py-2">{{ __('messages.outstanding') }}</th>
                        <th class="py-2">{{ __('messages.overdue') }}</th>
                        <th class="py-2">{{ __('messages.penalties') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                    @foreach($pr['group_risk'] as $gr)
                        <tr>
                            <td class="py-2 font-semibold">{{ $gr['group_name'] }}</td>
                            <td class="py-2">{{ $gr['loan_officer'] }}</td>
                            <td class="py-2">TZS {{ number_format($gr['disbursed'], 0) }}</td>
                            <td class="py-2">TZS {{ number_format($gr['outstanding'], 0) }}</td>
                            <td class="py-2 font-bold {{ $gr['overdue_loans'] > 0 ? 'text-red-600' : '' }}">{{ $gr['overdue_loans'] }}</td>
                            <td class="py-2">TZS {{ number_format($gr['penalty_balance'], 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    {{-- 3. Financial & Management Indicators --}}
    @elseif($activeTab === 'indicators')
        @php $ind = $report['indicators']; @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="text-xs text-slate-400">{{ __('messages.collection_rate') }}</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $ind['collection_rate_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="text-xs text-slate-400">{{ __('messages.portfolio_yield') }}</div>
                <div class="text-2xl font-extrabold mt-1">{{ $ind['portfolio_yield_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="text-xs text-slate-400">{{ __('messages.oss_ratio') }}</div>
                <div class="text-2xl font-extrabold {{ $ind['oss_pct'] >= 100 ? 'text-emerald-600' : 'text-amber-600' }} mt-1">{{ $ind['oss_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="text-xs text-slate-400">{{ __('messages.operating_expense_ratio') }}</div>
                <div class="text-2xl font-extrabold mt-1">{{ $ind['operating_expense_ratio_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="text-xs text-slate-400">{{ __('messages.average_loan_size') }}</div>
                <div class="text-2xl font-extrabold mt-1">TZS {{ number_format($ind['average_loan_size'], 0) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="text-xs text-slate-400">{{ __('messages.portfolio_per_officer') }}</div>
                <div class="text-2xl font-extrabold mt-1">TZS {{ number_format($ind['portfolio_per_officer'], 0) }}</div>
            </div>
        </div>

    {{-- 4. Budget vs. Actual --}}
    @elseif($activeTab === 'budget')
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm overflow-x-auto">
            <h3 class="text-sm font-bold mb-3">{{ __('messages.tab_budget_actual') }} ({{ $report['filters']['period'] }})</h3>
            <table class="w-full text-left text-xs">
                <thead class="text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-2">{{ __('messages.category') }}</th>
                        <th class="py-2">{{ __('messages.budgeted') }}</th>
                        <th class="py-2">{{ __('messages.actual') }}</th>
                        <th class="py-2">{{ __('messages.variance') }}</th>
                        <th class="py-2">{{ __('messages.achievement') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                    @foreach($report['budget_vs_actual'] as $row)
                        <tr>
                            <td class="py-2.5 font-semibold">{{ $row['category'] }}</td>
                            <td class="py-2.5">TZS {{ number_format($row['budgeted'], 0) }}</td>
                            <td class="py-2.5 font-bold">TZS {{ number_format($row['actual'], 0) }}</td>
                            <td class="py-2.5 font-semibold {{ $row['variance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $row['variance'] >= 0 ? '+' : '' }}TZS {{ number_format($row['variance'], 0) }}
                            </td>
                            <td class="py-2.5 font-bold">{{ $row['achievement_pct'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    {{-- 5. Operational Summary --}}
    @elseif($activeTab === 'operational')
        @php $op = $report['operational_summary']; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.groups_borrowers') }}</div>
                <div class="text-lg font-extrabold mt-1">{{ $op['total_groups'] }} • {{ $op['total_borrowers'] }}</div>
                <div class="text-[11px] text-slate-500">{{ __('messages.female') }}: {{ $op['female_borrowers'] }} • {{ __('messages.male') }}: {{ $op['male_borrowers'] }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.period_disbursements') }}</div>
                <div class="text-lg font-extrabold mt-1">TZS {{ number_format($op['period_disbursements'], 0) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.period_collections') }}</div>
                <div class="text-lg font-extrabold text-emerald-600 mt-1">TZS {{ number_format($op['period_collections'], 0) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.all_time_disb_coll') }}</div>
                <div class="text-xs font-bold mt-1">{{ __('messages.disbursed') }}: TZS {{ number_format($op['all_time_disbursements'], 0) }}</div>
                <div class="text-xs font-bold text-emerald-600">TZS {{ number_format($op['all_time_collections'], 0) }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm overflow-x-auto">
            <h3 class="text-sm font-bold mb-3">{{ __('messages.officer_productivity') }}</h3>
            <table class="w-full text-left text-xs">
                <thead class="text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-2">{{ __('messages.loan_officer') }}</th>
                        <th class="py-2">{{ __('messages.groups') }}</th>
                        <th class="py-2">{{ __('messages.members') }}</th>
                        <th class="py-2">{{ __('messages.disbursed') }}</th>
                        <th class="py-2">{{ __('messages.outstanding') }}</th>
                        <th class="py-2">{{ __('messages.overdue') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                    @forelse($op['officer_productivity'] as $of)
                        <tr>
                            <td class="py-2 font-semibold">{{ $of['officer_name'] }}</td>
                            <td class="py-2">{{ $of['groups_count'] }}</td>
                            <td class="py-2">{{ $of['members_count'] }}</td>
                            <td class="py-2">TZS {{ number_format($of['disbursed'], 0) }}</td>
                            <td class="py-2">TZS {{ number_format($of['outstanding'], 0) }}</td>
                            <td class="py-2 font-bold {{ $of['overdue_loans'] > 0 ? 'text-red-600' : '' }}">{{ $of['overdue_loans'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-slate-400">-</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- 6. Audit & Compliance --}}
    @else
        @php $au = $report['audit_compliance']; $kyc = $au['kyc']; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.id_number_coverage') }}</div>
                <div class="text-xl font-extrabold mt-1">{{ $kyc['id_number_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.id_photo_coverage') }}</div>
                <div class="text-xl font-extrabold mt-1">{{ $kyc['id_image_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.profile_photo_coverage') }}</div>
                <div class="text-xl font-extrabold mt-1">{{ $kyc['photo_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.referee_coverage') }}</div>
                <div class="text-xl font-extrabold mt-1">{{ $kyc['referee_pct'] }}%</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="text-xs text-slate-400">{{ __('messages.mpesa_recon_summary') }}</div>
                <div class="text-base font-extrabold mt-1 text-emerald-600">{{ $au['mpesa_reconciled'] }} <span class="text-slate-400">/</span> <span class="text-red-600">{{ $au['mpesa_unmatched'] }}</span></div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-2 shadow-sm">
            <h3 class="text-sm font-bold">{{ __('messages.audit_trail_latest') }}</h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-700/70 text-xs">
                @foreach($au['recent_audits'] as $log)
                    <div class="py-2 flex flex-wrap justify-between gap-2">
                        <div>
                            <span class="font-bold text-[#B3261E] dark:text-[#F2B8B5]">{{ $log['action'] }}</span>
                            <span class="font-semibold ml-1">{{ $log['target'] }}</span>
                            <span class="text-slate-500 ml-1">— {{ $log['details'] }}</span>
                        </div>
                        <div class="text-slate-400">{{ $log['actor_name'] }} • {{ $log['created_at'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Record Financial Entry Modal --}}
    @if($showEntryModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('messages.record_financial_entry') }}</h3>
            <form wire:submit="saveEntry" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.entry_type') }}</label>
                    <select wire:model="entryType" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($entryTypes as $k => $lbl)
                            <option value="{{ $k }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.entry_category_hint') }}</label>
                    <input type="text" wire:model="entryCategory" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('entryCategory') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.amount_tzs') }}</label>
                        <input type="number" step="0.01" wire:model="entryAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('entryAmount') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Date</label>
                        <input type="date" wire:model="entryDate" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.description_optional') }}</label>
                    <input type="text" wire:model="entryDescription" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="$set('showEntryModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Set Budget Modal --}}
    @if($showBudgetModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('messages.set_budget_target') }}</h3>
            <form wire:submit="saveBudget" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.month_period') }}</label>
                    <input type="month" wire:model="budgetPeriod" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.category') }}</label>
                    <select wire:model="budgetCategory" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($budgetCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.budgeted_amount_tzs') }}</label>
                    <input type="number" step="0.01" wire:model="budgetAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('budgetAmount') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="$set('showBudgetModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
