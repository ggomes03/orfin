import { Badge } from '@/components/ui/badge';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

type BudgetAllocationItem = {
    key: string;
    label: string;
    percentage: number;
    amount: number;
};

type MonthlyDetail = {
    month: number;
    referenceIncomeAmount: number;
    items: BudgetAllocationItem[];
    unallocatedPercentage: number;
    unallocatedAmount: number;
};

type AnnualDetail = {
    referenceIncomeAmount: number;
    items: BudgetAllocationItem[];
    unallocatedPercentage: number;
    unallocatedAmount: number;
};

const colors = ['#16a34a', '#0284c7', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];

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

export default function MonthlyEntriesExpensesDetail({
    detail,
    annualDetail,
    exerciseYear,
}: {
    detail: MonthlyDetail | null;
    annualDetail: AnnualDetail;
    exerciseYear: number;
}) {
    const isMonthlyView = detail !== null;
    const currentDetail = detail ?? annualDetail;

    const chartData = currentDetail.items
        .map((item, index) => ({
            name: item.label,
            value: item.percentage,
            color: colors[index % colors.length],
        }))
        .filter((item) => item.value > 0);

    if (currentDetail.unallocatedPercentage > 0) {
        chartData.push({
            name: 'Nao alocado',
            value: currentDetail.unallocatedPercentage,
            color: '#e5e7eb',
        });
    }

    const effectiveChartData = chartData.length > 0
        ? chartData
        : [{ name: 'Nao alocado', value: 100, color: '#e5e7eb' }];

    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
            <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium text-muted-foreground">
                    {isMonthlyView ? 'Orcamento mensal' : 'Orcamento anual'}
                </p>
                <Badge variant="secondary">
                    {isMonthlyView ? formatMonthLabel(detail.month) : `Ano ${exerciseYear}`}
                </Badge>
            </div>

            {!isMonthlyView && (
                <p className="mt-2 text-xs text-muted-foreground">
                    Visao anual exibida por padrao. Clique em um mes da tabela para ver os valores mensais.
                </p>
            )}

            <div className="mt-4 grid gap-2 md:grid-cols-3">
                <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100">
                    Entradas do periodo: {currencyFormatter.format(currentDetail.referenceIncomeAmount)}
                </Badge>
                <Badge className="bg-blue-100 text-blue-800 hover:bg-blue-100">
                    Nao alocado: {currentDetail.unallocatedPercentage}%
                </Badge>
                <Badge className="border border-blue-300 bg-blue-50 text-blue-800 hover:bg-blue-50 dark:border-blue-700">
                    Valor nao alocado: {currencyFormatter.format(currentDetail.unallocatedAmount)}
                </Badge>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-[320px_1fr]">
                <div className="relative h-80 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={effectiveChartData}
                                dataKey="value"
                                nameKey="name"
                                innerRadius={52}
                                outerRadius={130}
                                stroke="none"
                            >
                                {effectiveChartData.map((entry) => (
                                    <Cell key={entry.name} fill={entry.color} />
                                ))}
                            </Pie>
                            <Tooltip
                                formatter={(value) => `${Number(value ?? 0)}%`}
                                contentStyle={{ borderRadius: '10px', borderColor: '#d1d5db' }}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                    <div className="pointer-events-none absolute inset-0 grid place-items-center">
                        <div className="flex h-28 w-28 flex-col items-center justify-center rounded-full bg-background/95 text-center">
                            <p className="text-[10px] uppercase leading-tight tracking-wide text-muted-foreground">
                                Total
                            </p>
                            <p className="text-[10px] uppercase leading-tight tracking-wide text-muted-foreground">
                                alocado
                            </p>
                            <p className="mt-1 text-xl leading-none font-semibold">
                                {100 - currentDetail.unallocatedPercentage}%
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-2">
                    {currentDetail.items.map((item, index) => (
                        <div
                            key={item.key}
                            className="flex items-center justify-between rounded-md border border-sidebar-border/60 p-3 dark:border-sidebar-border"
                        >
                            <div className="flex items-center gap-2">
                                <span
                                    className="h-3 w-3 rounded-full"
                                    style={{ backgroundColor: colors[index % colors.length] }}
                                />
                                <p className="text-sm font-medium">{item.label}</p>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-semibold">{item.percentage}%</p>
                                <p className="text-xs text-muted-foreground">
                                    {currencyFormatter.format(item.amount)}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
