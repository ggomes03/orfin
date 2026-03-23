import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Entradas',
        href: '/financeiro/entradas',
    },
];

type IncomeSource = {
    id: number;
    type: string;
    description: string;
    monthly_amount: string;
};

type IncomeEntry = {
    id: number;
    entry_type: 'source' | 'simple';
    description: string;
    amount: string;
    entry_date: string;
    income_source_id: number | null;
    income_source?: {
        type: string;
        description: string;
    } | null;
};

type SourceTypeOption = {
    value: string;
    label: string;
};

export default function IncomeEntriesPage({
    incomeSources,
    incomeEntries,
    sourceTypeOptions,
    status,
}: {
    incomeSources: IncomeSource[];
    incomeEntries: IncomeEntry[];
    sourceTypeOptions: SourceTypeOption[];
    status?: string;
}) {
    const sourceForm = useForm({
        type: sourceTypeOptions[0]?.value ?? 'salary',
        description: '',
        monthly_amount: '',
    });

    const entryForm = useForm({
        entry_mode: (incomeSources.length > 0 ? 'source' : 'simple') as 'source' | 'simple',
        income_source_id: incomeSources[0]?.id ? String(incomeSources[0].id) : '',
        description: '',
        amount: '',
        entry_date: new Date().toISOString().slice(0, 10),
    });

    const sourceTypeLabelByValue = sourceTypeOptions.reduce(
        (accumulator, option) => {
            accumulator[option.value] = option.label;
            return accumulator;
        },
        {} as Record<string, string>,
    );

    const currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

    const dateFormatter = new Intl.DateTimeFormat('pt-BR');

    const formatEntryDate = (value: string) => {
        const normalizedValue = value.includes('T')
            ? value
            : value.includes(' ')
              ? value.replace(' ', 'T')
              : `${value}T00:00:00`;

        const parsedDate = new Date(normalizedValue);

        if (Number.isNaN(parsedDate.getTime())) {
            return value;
        }

        return dateFormatter.format(parsedDate);
    };

    const submitSource = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        sourceForm.post('/financeiro/fontes-renda', {
            preserveScroll: true,
            onSuccess: () => {
                sourceForm.reset('description', 'monthly_amount');
            },
        });
    };

    const submitEntry = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        entryForm.post('/financeiro/entradas', {
            preserveScroll: true,
            onSuccess: () => {
                if (entryForm.data.entry_mode === 'simple') {
                    entryForm.reset('description', 'amount');
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Entradas" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {status && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {status}
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Nova fonte de renda mensal</CardTitle>
                            <CardDescription>
                                Cadastre receitas fixas, como salario, complemento, prestacao de servico ou bolsa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={submitSource}>
                                <div className="grid gap-2">
                                    <Label htmlFor="source_type">Tipo</Label>
                                    <select
                                        id="source_type"
                                        value={sourceForm.data.type}
                                        onChange={(event) => sourceForm.setData('type', event.target.value)}
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                    >
                                        {sourceTypeOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={sourceForm.errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="source_description">Descricao</Label>
                                    <Input
                                        id="source_description"
                                        value={sourceForm.data.description}
                                        onChange={(event) => sourceForm.setData('description', event.target.value)}
                                        placeholder="Ex: Salario empresa X"
                                    />
                                    <InputError message={sourceForm.errors.description} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="source_monthly_amount">Valor mensal</Label>
                                    <Input
                                        id="source_monthly_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={sourceForm.data.monthly_amount}
                                        onChange={(event) => sourceForm.setData('monthly_amount', event.target.value)}
                                        placeholder="0,00"
                                    />
                                    <InputError message={sourceForm.errors.monthly_amount} />
                                </div>

                                <Button type="submit" disabled={sourceForm.processing}>
                                    Cadastrar fonte
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Nova entrada</CardTitle>
                            <CardDescription>
                                Fontes do tipo salario sao fixas e entram automaticamente no ano. Aqui voce lanca fontes nao salariais ou entradas avulsas.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={submitEntry}>
                                <div className="grid gap-2">
                                    <Label htmlFor="entry_mode">Tipo de entrada</Label>
                                    <select
                                        id="entry_mode"
                                        value={entryForm.data.entry_mode}
                                        onChange={(event) => {
                                            const mode = event.target.value as 'source' | 'simple';
                                            entryForm.setData('entry_mode', mode);
                                        }}
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                    >
                                        <option value="source">Entrada por fonte de renda</option>
                                        <option value="simple">Entrada simples</option>
                                    </select>
                                    <InputError message={entryForm.errors.entry_mode} />
                                </div>

                                {entryForm.data.entry_mode === 'source' ? (
                                    <div className="grid gap-2">
                                        <Label htmlFor="income_source_id">Fonte de renda</Label>
                                        <select
                                            id="income_source_id"
                                            value={entryForm.data.income_source_id}
                                            onChange={(event) =>
                                                entryForm.setData('income_source_id', event.target.value)
                                            }
                                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            <option value="">Selecione uma fonte</option>
                                            {incomeSources.map((source) => (
                                                <option key={source.id} value={String(source.id)}>
                                                    {source.description} ({currencyFormatter.format(Number(source.monthly_amount))})
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={entryForm.errors.income_source_id} />
                                    </div>
                                ) : (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="entry_description">Descricao</Label>
                                            <Input
                                                id="entry_description"
                                                value={entryForm.data.description}
                                                onChange={(event) =>
                                                    entryForm.setData('description', event.target.value)
                                                }
                                                placeholder="Ex: Venda pontual"
                                            />
                                            <InputError message={entryForm.errors.description} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="entry_amount">Valor</Label>
                                            <Input
                                                id="entry_amount"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={entryForm.data.amount}
                                                onChange={(event) => entryForm.setData('amount', event.target.value)}
                                                placeholder="0,00"
                                            />
                                            <InputError message={entryForm.errors.amount} />
                                        </div>
                                    </>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="entry_date">Data da entrada</Label>
                                    <Input
                                        id="entry_date"
                                        type="date"
                                        value={entryForm.data.entry_date}
                                        onChange={(event) => entryForm.setData('entry_date', event.target.value)}
                                    />
                                    <InputError message={entryForm.errors.entry_date} />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={
                                        entryForm.processing ||
                                        (entryForm.data.entry_mode === 'source' &&
                                            incomeSources.length === 0)
                                    }
                                >
                                    Cadastrar entrada
                                </Button>
                                {entryForm.data.entry_mode === 'source' && incomeSources.length === 0 && (
                                    <p className="text-sm text-amber-700">
                                        Cadastre ao menos uma fonte de renda nao salarial para usar este tipo de entrada.
                                    </p>
                                )}
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Entradas cadastradas</CardTitle>
                        <CardDescription>
                            Historico das entradas registradas, incluindo origem e data.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {incomeEntries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nenhuma entrada cadastrada ate o momento.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[720px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="px-2 py-2 font-medium">Descricao</th>
                                            <th className="px-2 py-2 font-medium">Tipo</th>
                                            <th className="px-2 py-2 font-medium">Valor</th>
                                            <th className="px-2 py-2 font-medium">Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {incomeEntries.map((entry) => (
                                            <tr key={entry.id} className="border-b last:border-0">
                                                <td className="px-2 py-3">{entry.description}</td>
                                                <td className="px-2 py-3">
                                                    {entry.entry_type === 'source' ? (
                                                        <Badge variant="secondary">
                                                            Fonte mensal
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline">
                                                            Entrada simples
                                                        </Badge>
                                                    )}
                                                    {entry.income_source?.type && (
                                                        <span className="ml-2 text-xs text-muted-foreground">
                                                            {sourceTypeLabelByValue[entry.income_source.type] ??
                                                                entry.income_source.type}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-2 py-3">
                                                    {currencyFormatter.format(Number(entry.amount))}
                                                </td>
                                                <td className="px-2 py-3">
                                                    {formatEntryDate(entry.entry_date)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
