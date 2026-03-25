import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type BudgetItem = {
    key: string;
    label: string;
    percentage: number;
};

const colors = ['#16a34a', '#0284c7', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Controle de orcamento',
        href: '/financeiro/controle-orcamento',
    },
];

const currencyLikePercent = (value: number) => `${value}%`;

export default function BudgetControlPage({
    items,
    totalPercentage,
    remainingPercentage,
    status,
}: {
    items: BudgetItem[];
    totalPercentage: number;
    remainingPercentage: number;
    status?: string;
}) {
    const initialData = items.reduce(
        (accumulator, item) => {
            accumulator[item.key] = item.percentage;
            return accumulator;
        },
        {} as Record<string, number>,
    );

    const form = useForm<Record<string, number>>(initialData);

    const total = items.reduce((sum, item) => sum + Number(form.data[item.key] ?? 0), 0);
    const remaining = Math.max(0, 100 - total);

    const chartData = items
        .map((item, index) => ({
            name: item.label,
            value: Number(form.data[item.key] ?? 0),
            color: colors[index % colors.length],
        }))
        .filter((item) => item.value > 0);

    if (remaining > 0) {
        chartData.push({
            name: 'Nao alocado',
            value: remaining,
            color: '#e5e7eb',
        });
    }

    const updatePercentage = (key: string, nextValue: number) => {
        const currentValue = Number(form.data[key] ?? 0);
        const totalWithoutCurrent = total - currentValue;
        const maxAllowed = Math.max(0, 100 - totalWithoutCurrent);
        const clamped = Math.max(0, Math.min(nextValue, maxAllowed));

        form.setData(key, clamped);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.put('/financeiro/controle-orcamento', {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Controle de orcamento" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {status && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {status}
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                        <h2 className="text-lg font-semibold">Distribuicao atual</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Soma total: {currencyLikePercent(total)}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Restante disponivel: {currencyLikePercent(remaining)}
                        </p>

                        <div className="mt-6 flex justify-center">
                            <div className="relative h-72 w-72" aria-label="Grafico de pizza da distribuicao de orcamento">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={chartData.length > 0 ? chartData : [{ name: 'Nao alocado', value: 100, color: '#e5e7eb' }]}
                                            dataKey="value"
                                            nameKey="name"
                                            innerRadius={48}
                                            outerRadius={132}
                                            stroke="none"
                                            paddingAngle={0}
                                        >
                                            {(chartData.length > 0
                                                ? chartData
                                                : [{ name: 'Nao alocado', value: 100, color: '#e5e7eb' }]
                                            ).map((entry) => (
                                                <Cell key={entry.name} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            formatter={(value) => `${Number(value ?? 0)}%`}
                                            contentStyle={{ borderRadius: '10px', borderColor: '#d1d5db' }}
                                            wrapperStyle={{ zIndex: 50 }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="pointer-events-none absolute inset-0 z-10 grid place-items-center">
                                    <div className="flex h-32 w-32 flex-col items-center justify-center rounded-full bg-background text-center">
                                        <p className="text-[10px] uppercase leading-tight tracking-wide text-muted-foreground">
                                            Total
                                        </p>
                                        <p className="text-[10px] uppercase leading-tight tracking-wide text-muted-foreground">
                                            alocado
                                        </p>
                                        <p className="mt-1 text-2xl leading-none font-semibold">{currencyLikePercent(total)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 grid gap-2">
                            {items.map((item, index) => (
                                <div key={item.key} className="flex items-center gap-2 text-sm">
                                    <span
                                        className="h-3 w-3 rounded-full"
                                        style={{ backgroundColor: colors[index % colors.length] }}
                                    />
                                    <span className="text-muted-foreground">{item.label}</span>
                                    <span className="ml-auto font-medium">
                                        {currencyLikePercent(Number(form.data[item.key] ?? 0))}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                        <h2 className="text-lg font-semibold">Controle de orcamento</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ajuste os percentuais com os controles. A soma nao pode passar de 100%.
                        </p>

                        <form className="mt-6 space-y-6" onSubmit={submit}>
                            {items.map((item) => {
                                const value = Number(form.data[item.key] ?? 0);
                                const maxForItem = value + remaining;

                                return (
                                    <div key={item.key} className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <label htmlFor={item.key} className="text-sm font-medium">
                                                {item.label}
                                            </label>
                                            <span className="text-sm font-semibold">
                                                {currencyLikePercent(value)}
                                            </span>
                                        </div>

                                        <input
                                            id={item.key}
                                            type="range"
                                            min={0}
                                            max={maxForItem}
                                            step={1}
                                            value={value}
                                            onChange={(event) =>
                                                updatePercentage(item.key, Number(event.target.value))
                                            }
                                            className="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200"
                                        />
                                    </div>
                                );
                            })}

                            <InputError message={form.errors.total} />

                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Banco atual: {currencyLikePercent(totalPercentage)} | Restante: {currencyLikePercent(remainingPercentage)}
                                </p>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Salvando...' : 'Salvar distribuicao'}
                                </Button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
