import { Head } from '@inertiajs/react';
import MonthlyEntriesExpensesDetail from '@/components/dashboard/monthly-entries-expenses-detail';
import MonthlyBalanceTable from '@/components/dashboard/monthly-balance-table';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

export default function Dashboard({
    annualIncomeAmount,
    annualExpenseAmount,
    annualBalanceAmount,
    exerciseYear,
    monthlyBalanceRows,
    annualBudgetDetail,
    selectedMonth,
    selectedMonthDetail,
}: {
    annualIncomeAmount: number;
    annualExpenseAmount: number;
    annualBalanceAmount: number;
    exerciseYear: number;
    selectedMonth: number | null;
    monthlyBalanceRows: {
        month: number;
        incomeAmount: number;
        expenseAmount: number;
        balanceAmount: number;
    }[];
    annualBudgetDetail: {
        referenceIncomeAmount: number;
        items: {
            key: string;
            label: string;
            percentage: number;
            amount: number;
        }[];
        unallocatedPercentage: number;
        unallocatedAmount: number;
    };
    selectedMonthDetail: {
        month: number;
        referenceIncomeAmount: number;
        items: {
            key: string;
            label: string;
            percentage: number;
            amount: number;
        }[];
        unallocatedPercentage: number;
        unallocatedAmount: number;
    } | null;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-emerald-700 bg-emerald-700 p-5 text-emerald-50">
                        <p className="text-sm text-emerald-100">
                            Entradas no ano do exercicio
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            }).format(annualIncomeAmount)}
                        </p>
                        <p className="mt-1 text-xs text-emerald-100">
                            Ano: {exerciseYear}
                        </p>
                    </div>
                    <div className="rounded-xl border border-red-700 bg-red-700 p-5 text-red-50">
                        <p className="text-sm text-red-100">
                            Saidas no ano do exercicio
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            }).format(annualExpenseAmount)}
                        </p>
                        <p className="mt-1 text-xs text-red-100">
                            Inclui projecao de saidas fixas
                        </p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            Saldo anual projetado
                        </p>
                        <p
                            className={
                                annualBalanceAmount >= 0
                                    ? 'mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400'
                                    : 'mt-2 text-2xl font-semibold text-red-600 dark:text-red-400'
                            }
                        >
                            {new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            }).format(annualBalanceAmount)}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Entradas menos saidas
                        </p>
                    </div>
                </div>
                <div className="grid min-h-[100vh] flex-1 gap-4 md:min-h-min md:grid-cols-2">
                    <div className="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <MonthlyBalanceTable rows={monthlyBalanceRows} selectedMonth={selectedMonth} />
                    </div>
                    <MonthlyEntriesExpensesDetail detail={selectedMonthDetail} annualDetail={annualBudgetDetail} exerciseYear={exerciseYear} />
                </div>
            </div>
        </AppLayout>
    );
}
