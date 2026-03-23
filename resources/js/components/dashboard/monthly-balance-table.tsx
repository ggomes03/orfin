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
}: {
    rows: MonthlyBalanceRow[];
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
                        <tr key={row.month} className="border-b last:border-0">
                            <td className="px-2 py-3 font-medium">
                                {formatMonthLabel(row.month)}
                            </td>
                            <td className="px-2 py-3">
                                {currencyFormatter.format(row.incomeAmount)}
                            </td>
                            <td className="px-2 py-3">
                                {currencyFormatter.format(row.expenseAmount)}
                            </td>
                            <td className="px-2 py-3 font-medium">
                                {currencyFormatter.format(row.balanceAmount)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
