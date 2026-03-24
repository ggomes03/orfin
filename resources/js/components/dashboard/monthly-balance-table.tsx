import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/react';

type MonthlyBalanceRow = {
    month: number;
    incomeAmount: number;
    expenseAmount: number;
    balanceAmount: number;
};

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const monthFormatter = new Intl.DateTimeFormat('pt-BR', {
    month: 'long',
});

function formatMonthLabel(month: number) {
    const date = new Date(2026, month - 1, 1);
    const [firstLetter = '', ...remainingLetters] = monthFormatter
        .format(date)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .split('');

    return `${firstLetter.toUpperCase()}${remainingLetters.join('')}`;
}

export default function MonthlyBalanceTable({
    rows,
    selectedMonth,
}: {
    rows: MonthlyBalanceRow[];
    selectedMonth: number | null;
}) {
    return (
        <div className="overflow-x-auto p-4 md:p-6">
            <table className="w-full min-w-[760px] text-sm">
                <thead>
                    <tr className="border-b text-left text-muted-foreground">
                        <th className="px-2 py-2 font-medium">Mes</th>
                        <th className="px-2 py-2 font-medium">Entradas</th>
                        <th className="px-2 py-2 font-medium">Saidas</th>
                        <th className="px-2 py-2 font-medium">Saldo do mes</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr
                            key={row.month}
                            className={
                                row.month === selectedMonth
                                    ? 'cursor-pointer border-b bg-emerald-50/40 transition-colors hover:bg-emerald-50/60 last:border-0 dark:bg-emerald-900/10 dark:hover:bg-emerald-900/20'
                                    : 'cursor-pointer border-b transition-colors hover:bg-muted/40 last:border-0'
                            }
                            onClick={() => {
                                router.get('/dashboard', { month: row.month }, { preserveScroll: true });
                            }}
                        >
                            <td className="px-2 py-3 font-medium">
                                {formatMonthLabel(row.month)}
                            </td>
                            <td className="px-2 py-3">
                                <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100">
                                    {currencyFormatter.format(row.incomeAmount)}
                                </Badge>
                            </td>
                            <td className="px-2 py-3">
                                <Badge className="bg-red-100 text-red-800 hover:bg-red-100">
                                    {currencyFormatter.format(row.expenseAmount)}
                                </Badge>
                            </td>
                            <td className="px-2 py-3 font-medium">
                                <Badge
                                    className={
                                        row.balanceAmount >= 0
                                            ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100'
                                            : 'bg-red-100 text-red-800 hover:bg-red-100'
                                    }
                                >
                                    {currencyFormatter.format(row.balanceAmount)}
                                </Badge>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
