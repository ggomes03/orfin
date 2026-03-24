import { Badge } from '@/components/ui/badge';

type MonthlyDetailItem = {
    id: number;
    description: string;
    amount: number;
    entryDate: string;
    typeLabel?: string | null;
};

type MonthlyDetail = {
    month: number;
    incomeItems: MonthlyDetailItem[];
    expenseItems: MonthlyDetailItem[];
    incomeTotalAmount: number;
    expenseTotalAmount: number;
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

function formatDate(value: string) {
    const isoDateMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!isoDateMatch) {
        return value;
    }

    const [, year, month, day] = isoDateMatch;

    return `${day}/${month}/${year}`;
}

function DetailList({
    title,
    items,
    emptyMessage,
    className,
    itemClassName,
}: {
    title: string;
    items: MonthlyDetailItem[];
    emptyMessage: string;
    className?: string;
    itemClassName?: string;
}) {
    return (
        <div className={`rounded-lg border p-3 ${className ?? 'border-sidebar-border/70 dark:border-sidebar-border'}`}>
            <p className="text-sm font-semibold">{title}</p>
            {items.length === 0 ? (
                <p className="mt-2 text-xs text-muted-foreground">{emptyMessage}</p>
            ) : (
                <div className="mt-3 space-y-2">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className={`rounded-md border p-2 ${itemClassName ?? 'border-sidebar-border/60 dark:border-sidebar-border'}`}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <p className="text-sm font-medium">{item.description}</p>
                                <Badge variant="outline">{currencyFormatter.format(item.amount)}</Badge>
                            </div>
                            <div className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                <span>{formatDate(item.entryDate)}</span>
                                {item.typeLabel && <span>• {item.typeLabel}</span>}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function MonthlyEntriesExpensesDetail({
    detail,
}: {
    detail: MonthlyDetail | null;
}) {
    if (!detail) {
        return (
            <div className="rounded-xl border border-dashed border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <p className="text-sm font-medium text-muted-foreground">
                    Detalhamento mensal
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    Clique em "Detalhar" em um mes da tabela para ver entradas e saidas deste periodo.
                </p>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
            <div className="flex items-center justify-between gap-2">
                <p className="text-sm font-medium text-muted-foreground">Detalhamento mensal</p>
                <Badge variant="secondary">{formatMonthLabel(detail.month)}</Badge>
            </div>

            <div className="mt-4 grid gap-2 md:grid-cols-3">
                <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100">
                    Entradas: {currencyFormatter.format(detail.incomeTotalAmount)}
                </Badge>
                <Badge className="border border-red-300 bg-red-100 text-red-800 hover:bg-red-100 dark:border-red-700">
                    Saidas: {currencyFormatter.format(detail.expenseTotalAmount)}
                </Badge>
                <Badge
                    className={
                        detail.balanceAmount >= 0
                            ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100'
                            : 'bg-red-100 text-red-800 hover:bg-red-100'
                    }
                >
                    Saldo: {currencyFormatter.format(detail.balanceAmount)}
                </Badge>
            </div>

            <div className="mt-4 grid gap-3 lg:grid-cols-2">
                <DetailList
                    title="Entradas do mes"
                    items={detail.incomeItems}
                    emptyMessage="Nenhuma entrada encontrada para este mes."
                />
                <DetailList
                    title="Saidas do mes"
                    items={detail.expenseItems}
                    emptyMessage="Nenhuma saida encontrada para este mes."
                    className="border-red-300 dark:border-red-700"
                    itemClassName="border-red-200 dark:border-red-800"
                />
            </div>
        </div>
    );
}
