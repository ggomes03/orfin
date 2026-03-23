import { Head } from '@inertiajs/react';
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
}: {
    annualIncomeAmount: number;
    annualExpenseAmount: number;
    annualBalanceAmount: number;
    exerciseYear: number;
    monthlyBalanceRows: {
        month: number;
        incomeAmount: number;
        expenseAmount: number;
        balanceAmount: number;
    }[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            Entradas no ano do exercicio
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            }).format(annualIncomeAmount)}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Ano: {exerciseYear}
                        </p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            Saidas no ano do exercicio
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
                            {new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            }).format(annualExpenseAmount)}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Inclui projecao de saidas fixas
                        </p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            Saldo anual projetado
                        </p>
                        <p className="mt-2 text-2xl font-semibold">
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
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <MonthlyBalanceTable rows={monthlyBalanceRows} />
                </div>
            </div>
        </AppLayout>
    );
}
