import { Head, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
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
        title: 'Saidas',
        href: '/financeiro/saidas',
    },
];

type ExpenseSource = {
    id: number;
    type: string;
    description: string;
    category_id: number | null;
    monthly_amount: string | null;
    monthly_amount_started_at: string | null;
    expense_category?: {
        id: number;
        name: string;
    } | null;
    amount_histories?: {
        amount: string;
        effective_from: string;
    }[];
};

type ExpenseEntry = {
    id: number;
    entry_type: 'source' | 'simple';
    description: string;
    category_id: number | null;
    amount: string;
    entry_date: string;
    expense_source_id: number | null;
    expense_source?: {
        type: string;
        description: string;
    } | null;
    expense_category?: {
        id: number;
        name: string;
    } | null;
};

type SourceTypeOption = {
    value: string;
    label: string;
};

type ExpenseCategoryOption = {
    value: string;
    label: string;
};

const monthOptions = [
    { value: '1', label: 'Janeiro' },
    { value: '2', label: 'Fevereiro' },
    { value: '3', label: 'Marco' },
    { value: '4', label: 'Abril' },
    { value: '5', label: 'Maio' },
    { value: '6', label: 'Junho' },
    { value: '7', label: 'Julho' },
    { value: '8', label: 'Agosto' },
    { value: '9', label: 'Setembro' },
    { value: '10', label: 'Outubro' },
    { value: '11', label: 'Novembro' },
    { value: '12', label: 'Dezembro' },
];

export default function ExpenseEntriesPage({
    fixedExpenseSources,
    expenseSources,
    expenseEntries,
    sourceTypeOptions,
    expenseCategoryOptions,
    exerciseYear,
    status,
}: {
    fixedExpenseSources: ExpenseSource[];
    expenseSources: ExpenseSource[];
    expenseEntries: ExpenseEntry[];
    sourceTypeOptions: SourceTypeOption[];
    expenseCategoryOptions: ExpenseCategoryOption[];
    exerciseYear: number;
    status?: string;
}) {
    const getTodayDateInputValue = () => {
        const now = new Date();
        const year = exerciseYear;
        const month = `${now.getMonth() + 1}`.padStart(2, '0');
        const day = `${now.getDate()}`.padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const sourceForm = useForm({
        type: sourceTypeOptions[0]?.value ?? 'fixed',
        description: '',
        category_id: expenseCategoryOptions[0]?.value ?? '',
        monthly_amount: '',
        effective_from: getTodayDateInputValue(),
    });

    const entryForm = useForm({
        entry_mode: (expenseSources.length > 0 ? 'source' : 'simple') as 'source' | 'simple',
        expense_source_id: expenseSources[0]?.id ? String(expenseSources[0].id) : '',
        description: '',
        category_id: expenseCategoryOptions[0]?.value ?? '',
        amount: '',
        entry_date: getTodayDateInputValue(),
    });

    const fixedSourceForm = useForm({
        description: '',
        category_id: '',
        monthly_amount: '',
        effective_from: '',
    });

    const simpleEntryForm = useForm({
        description: '',
        category_id: '',
        amount: '',
        entry_date: '',
    });

    const [editingFixedSourceId, setEditingFixedSourceId] = useState<number | null>(null);
    const [editingSimpleEntryId, setEditingSimpleEntryId] = useState<number | null>(null);
    const [editingEntryType, setEditingEntryType] = useState<'source' | 'simple' | null>(null);
    const [selectedMonthFilter, setSelectedMonthFilter] = useState<string>('all');

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

    const filteredExpenseEntries = useMemo(() => {
        if (selectedMonthFilter === 'all') {
            return expenseEntries;
        }

        const selectedMonthNumber = Number(selectedMonthFilter);

        return expenseEntries.filter((entry) => {
            const dateMatch = entry.entry_date.match(/^(\d{4})-(\d{2})-(\d{2})/);

            if (!dateMatch) {
                return false;
            }

            return Number(dateMatch[2]) === selectedMonthNumber;
        });
    }, [expenseEntries, selectedMonthFilter]);

    const isFixedSource = sourceForm.data.type === 'fixed';

    const formatEntryDate = (value: string) => {
        const isoDateMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (!isoDateMatch) {
            return value;
        }

        const [, year, month, day] = isoDateMatch;

        return `${day}/${month}/${year}`;
    };

    const normalizeDateInputValue = (value: string) => {
        const isoDateMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (!isoDateMatch) {
            return '';
        }

        return `${isoDateMatch[1]}-${isoDateMatch[2]}-${isoDateMatch[3]}`;
    };

    const submitSource = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        sourceForm.post('/financeiro/fontes-saida', {
            preserveScroll: true,
            onSuccess: () => {
                sourceForm.reset('description', 'monthly_amount');
                sourceForm.setData('effective_from', getTodayDateInputValue());
            },
        });
    };

    const submitEntry = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        entryForm.post('/financeiro/saidas', {
            preserveScroll: true,
            onSuccess: () => {
                entryForm.reset('description', 'amount');
            },
        });
    };

    const startEditingFixedSource = (source: ExpenseSource) => {
        setEditingFixedSourceId(source.id);
        fixedSourceForm.setData('description', source.description);
        fixedSourceForm.setData('category_id', source.category_id ? String(source.category_id) : '');
        fixedSourceForm.setData('monthly_amount', source.monthly_amount ?? '');
        fixedSourceForm.setData('effective_from', normalizeDateInputValue(source.monthly_amount_started_at ?? ''));
        fixedSourceForm.clearErrors();
    };

    const cancelEditingFixedSource = () => {
        setEditingFixedSourceId(null);
        fixedSourceForm.reset();
        fixedSourceForm.clearErrors();
    };

    const submitFixedSourceUpdate = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingFixedSourceId === null) {
            return;
        }

        fixedSourceForm.patch(`/financeiro/fontes-saida-fixas/${editingFixedSourceId}`, {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditingFixedSource();
            },
        });
    };

    const deleteFixedSource = (sourceId: number) => {
        const confirmed = window.confirm('Deseja realmente remover esta saida fixa?');

        if (!confirmed) {
            return;
        }

        router.delete(`/financeiro/fontes-saida-fixas/${sourceId}`, {
            preserveScroll: true,
        });
    };

    const startEditingSimpleEntry = (entry: ExpenseEntry) => {
        setEditingSimpleEntryId(entry.id);
        setEditingEntryType(entry.entry_type);
        simpleEntryForm.setData('description', entry.description);
        simpleEntryForm.setData('category_id', entry.category_id ? String(entry.category_id) : '');
        simpleEntryForm.setData('amount', entry.amount);
        simpleEntryForm.setData('entry_date', normalizeDateInputValue(entry.entry_date));
        simpleEntryForm.clearErrors();
    };

    const cancelEditingSimpleEntry = () => {
        setEditingSimpleEntryId(null);
        setEditingEntryType(null);
        simpleEntryForm.reset();
        simpleEntryForm.clearErrors();
    };

    const submitSimpleEntryUpdate = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingSimpleEntryId === null) {
            return;
        }

        const updatePath = editingEntryType === 'source'
            ? `/financeiro/saidas-fonte/${editingSimpleEntryId}`
            : `/financeiro/saidas-avulsas/${editingSimpleEntryId}`;

        simpleEntryForm.patch(updatePath, {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditingSimpleEntry();
            },
        });
    };

    const deleteSimpleEntry = (entryId: number, entryType: 'source' | 'simple') => {
        const confirmed = window.confirm('Deseja realmente remover esta saida?');

        if (!confirmed) {
            return;
        }

        const deletePath = entryType === 'source'
            ? `/financeiro/saidas-fonte/${entryId}`
            : `/financeiro/saidas-avulsas/${entryId}`;

        router.delete(deletePath, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Saidas" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="rounded-lg border border-sidebar-border/70 bg-background px-4 py-2 text-sm text-muted-foreground dark:border-sidebar-border">
                    Exercicio selecionado: <span className="font-semibold text-foreground">{exerciseYear}</span>
                </div>

                {status && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {status}
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Nova fonte de saida</CardTitle>
                            <CardDescription>
                                Fontes fixas entram na projecao anual automaticamente. Cartao de credito e lancado manualmente mes a mes.
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
                                        placeholder="Ex: Aluguel"
                                    />
                                    <InputError message={sourceForm.errors.description} />
                                </div>

                                {isFixedSource && (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="source_category_id">Categoria da saida fixa</Label>
                                            <select
                                                id="source_category_id"
                                                value={sourceForm.data.category_id}
                                                onChange={(event) => sourceForm.setData('category_id', event.target.value)}
                                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                <option value="">Selecione uma categoria</option>
                                                {expenseCategoryOptions.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={sourceForm.errors.category_id} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="source_monthly_amount">Valor mensal fixo</Label>
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

                                        <div className="grid gap-2">
                                            <Label htmlFor="source_effective_from">Vigente a partir de</Label>
                                            <Input
                                                id="source_effective_from"
                                                type="date"
                                                value={sourceForm.data.effective_from}
                                                onChange={(event) => sourceForm.setData('effective_from', event.target.value)}
                                            />
                                            <InputError message={sourceForm.errors.effective_from} />
                                        </div>
                                    </>
                                )}

                                <Button type="submit" disabled={sourceForm.processing}>
                                    Cadastrar fonte
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Nova saida</CardTitle>
                            <CardDescription>
                                Use saida por fonte para lancamentos de cartao de credito ou saida simples para gastos avulsos.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={submitEntry}>
                                <div className="grid gap-2">
                                    <Label htmlFor="entry_mode">Tipo de saida</Label>
                                    <select
                                        id="entry_mode"
                                        value={entryForm.data.entry_mode}
                                        onChange={(event) => {
                                            const mode = event.target.value as 'source' | 'simple';
                                            entryForm.setData('entry_mode', mode);
                                        }}
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                    >
                                        <option value="source">Saida por fonte (cartao)</option>
                                        <option value="simple">Saida simples</option>
                                    </select>
                                    <InputError message={entryForm.errors.entry_mode} />
                                </div>

                                {entryForm.data.entry_mode === 'source' ? (
                                    <div className="grid gap-2">
                                        <Label htmlFor="expense_source_id">Fonte de saida</Label>
                                        <select
                                            id="expense_source_id"
                                            value={entryForm.data.expense_source_id}
                                            onChange={(event) =>
                                                entryForm.setData('expense_source_id', event.target.value)
                                            }
                                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            <option value="">Selecione uma fonte</option>
                                            {expenseSources.map((source) => (
                                                <option key={source.id} value={String(source.id)}>
                                                    {source.description}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={entryForm.errors.expense_source_id} />
                                    </div>
                                ) : (
                                    <div className="grid gap-2">
                                        <Label htmlFor="entry_description">Descricao</Label>
                                        <Input
                                            id="entry_description"
                                            value={entryForm.data.description}
                                            onChange={(event) =>
                                                entryForm.setData('description', event.target.value)
                                            }
                                            placeholder="Ex: Compra avulsa"
                                        />
                                        <InputError message={entryForm.errors.description} />
                                    </div>
                                )}

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

                                <div className="grid gap-2">
                                    <Label htmlFor="entry_category">Categoria</Label>
                                    <select
                                        id="entry_category"
                                        value={entryForm.data.category_id}
                                        onChange={(event) => entryForm.setData('category_id', event.target.value)}
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                    >
                                        <option value="">Selecione uma categoria</option>
                                        {expenseCategoryOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={entryForm.errors.category_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="entry_date">Data da saida</Label>
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
                                            expenseSources.length === 0)
                                    }
                                >
                                    Cadastrar saida
                                </Button>
                                {entryForm.data.entry_mode === 'source' && expenseSources.length === 0 && (
                                    <p className="text-sm text-amber-700">
                                        Cadastre ao menos uma fonte de cartao de credito para usar este tipo de saida.
                                    </p>
                                )}
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Saidas fixas cadastradas</CardTitle>
                        <CardDescription>
                            Visualize, edite e exclua as saidas que entram automaticamente na projecao anual.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {fixedExpenseSources.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nenhuma saida fixa cadastrada ate o momento.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[980px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="px-2 py-2 font-medium">Descricao</th>
                                            <th className="px-2 py-2 font-medium">Categoria</th>
                                            <th className="px-2 py-2 font-medium">Valor mensal</th>
                                            <th className="px-2 py-2 font-medium">Vigente desde</th>
                                            <th className="px-2 py-2 font-medium">Historico</th>
                                            <th className="px-2 py-2 font-medium">Acoes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {fixedExpenseSources.map((source) => (
                                            <tr key={source.id} className="border-b last:border-0">
                                                {editingFixedSourceId === source.id ? (
                                                    <td colSpan={6} className="px-2 py-3">
                                                        <form
                                                            className="grid gap-3 md:grid-cols-[2fr_1fr_1fr_1fr_auto] md:items-end"
                                                            onSubmit={submitFixedSourceUpdate}
                                                        >
                                                            <div className="grid gap-1">
                                                                <Label htmlFor="fixed_source_description">
                                                                    Descricao
                                                                </Label>
                                                                <Input
                                                                    id="fixed_source_description"
                                                                    value={fixedSourceForm.data.description}
                                                                    onChange={(event) =>
                                                                        fixedSourceForm.setData(
                                                                            'description',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={fixedSourceForm.errors.description}
                                                                />
                                                            </div>

                                                            <div className="grid gap-1">
                                                                <Label htmlFor="fixed_source_category_id">
                                                                    Categoria
                                                                </Label>
                                                                <select
                                                                    id="fixed_source_category_id"
                                                                    value={fixedSourceForm.data.category_id}
                                                                    onChange={(event) =>
                                                                        fixedSourceForm.setData(
                                                                            'category_id',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                                                >
                                                                    <option value="">Selecione uma categoria</option>
                                                                    {expenseCategoryOptions.map((option) => (
                                                                        <option key={option.value} value={option.value}>
                                                                            {option.label}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                                <InputError
                                                                    message={fixedSourceForm.errors.category_id}
                                                                />
                                                            </div>

                                                            <div className="grid gap-1">
                                                                <Label htmlFor="fixed_source_monthly_amount">
                                                                    Valor mensal
                                                                </Label>
                                                                <Input
                                                                    id="fixed_source_monthly_amount"
                                                                    type="number"
                                                                    min="0"
                                                                    step="0.01"
                                                                    value={fixedSourceForm.data.monthly_amount}
                                                                    onChange={(event) =>
                                                                        fixedSourceForm.setData(
                                                                            'monthly_amount',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={fixedSourceForm.errors.monthly_amount}
                                                                />
                                                            </div>

                                                            <div className="grid gap-1">
                                                                <Label htmlFor="fixed_source_effective_from">
                                                                    Vigente a partir de
                                                                </Label>
                                                                <Input
                                                                    id="fixed_source_effective_from"
                                                                    type="date"
                                                                    value={fixedSourceForm.data.effective_from}
                                                                    onChange={(event) =>
                                                                        fixedSourceForm.setData(
                                                                            'effective_from',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={fixedSourceForm.errors.effective_from}
                                                                />
                                                            </div>

                                                            <div className="flex gap-2">
                                                                <Button
                                                                    type="submit"
                                                                    disabled={fixedSourceForm.processing}
                                                                >
                                                                    Salvar
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={cancelEditingFixedSource}
                                                                >
                                                                    Cancelar
                                                                </Button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                ) : (
                                                    <>
                                                        <td className="px-2 py-3">{source.description}</td>
                                                        <td className="px-2 py-3">
                                                            <Badge variant="outline">
                                                                {source.expense_category?.name ?? 'Sem categoria'}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            {currencyFormatter.format(
                                                                Number(source.monthly_amount),
                                                            )}
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            {source.monthly_amount_started_at
                                                                ? formatEntryDate(source.monthly_amount_started_at)
                                                                : '-'}
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            <div className="space-y-1 text-xs">
                                                                {(source.amount_histories ?? []).slice(0, 3).map((history) => (
                                                                    <p
                                                                        key={`${source.id}-${history.effective_from}-${history.amount}`}
                                                                        className="text-muted-foreground"
                                                                    >
                                                                        {formatEntryDate(history.effective_from)}: {currencyFormatter.format(Number(history.amount))}
                                                                    </p>
                                                                ))}
                                                            </div>
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            <div className="flex gap-2">
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        startEditingFixedSource(source)
                                                                    }
                                                                >
                                                                    Editar
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="destructive"
                                                                    onClick={() =>
                                                                        deleteFixedSource(source.id)
                                                                    }
                                                                >
                                                                    Excluir
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Saidas cadastradas</CardTitle>
                        <CardDescription>
                            Historico das saidas registradas, incluindo origem e data.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 grid gap-2 md:max-w-xs">
                            <Label htmlFor="expense_month_filter">Filtrar por mes</Label>
                            <select
                                id="expense_month_filter"
                                value={selectedMonthFilter}
                                onChange={(event) => setSelectedMonthFilter(event.target.value)}
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <option value="all">Todos os meses</option>
                                {monthOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {filteredExpenseEntries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {selectedMonthFilter === 'all'
                                    ? 'Nenhuma saida cadastrada ate o momento.'
                                    : 'Nenhuma saida encontrada para o mes selecionado.'}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[720px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="px-2 py-2 font-medium">Descricao</th>
                                            <th className="px-2 py-2 font-medium">Tipo</th>
                                            <th className="px-2 py-2 font-medium">Categoria</th>
                                            <th className="px-2 py-2 font-medium">Valor</th>
                                            <th className="px-2 py-2 font-medium">Data</th>
                                            <th className="px-2 py-2 font-medium">Acoes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredExpenseEntries.map((entry) => (
                                            <tr key={entry.id} className="border-b last:border-0">
                                                {editingSimpleEntryId === entry.id ? (
                                                    <td colSpan={6} className="px-2 py-3">
                                                        <form
                                                            className="grid gap-3 md:grid-cols-[2fr_1fr_1fr_1fr_auto] md:items-end"
                                                            onSubmit={submitSimpleEntryUpdate}
                                                        >
                                                            <div className="grid gap-1">
                                                                <Label htmlFor="simple_entry_description">
                                                                    Descricao
                                                                </Label>
                                                                <Input
                                                                    id="simple_entry_description"
                                                                    value={simpleEntryForm.data.description}
                                                                    onChange={(event) =>
                                                                        simpleEntryForm.setData(
                                                                            'description',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={simpleEntryForm.errors.description}
                                                                />
                                                            </div>

                                                            <div className="grid gap-1">
                                                                <Label htmlFor="simple_entry_category">
                                                                    Categoria
                                                                </Label>
                                                                <select
                                                                    id="simple_entry_category"
                                                                    value={simpleEntryForm.data.category_id}
                                                                    onChange={(event) =>
                                                                        simpleEntryForm.setData(
                                                                            'category_id',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                                                >
                                                                    <option value="">
                                                                        Selecione uma categoria
                                                                    </option>
                                                                    {expenseCategoryOptions.map((option) => (
                                                                        <option key={option.value} value={option.value}>
                                                                            {option.label}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                                <InputError
                                                                    message={simpleEntryForm.errors.category_id}
                                                                />
                                                            </div>

                                                            <div className="grid gap-1">
                                                                <Label htmlFor="simple_entry_amount">
                                                                    Valor
                                                                </Label>
                                                                <Input
                                                                    id="simple_entry_amount"
                                                                    type="number"
                                                                    min="0"
                                                                    step="0.01"
                                                                    value={simpleEntryForm.data.amount}
                                                                    onChange={(event) =>
                                                                        simpleEntryForm.setData(
                                                                            'amount',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={simpleEntryForm.errors.amount}
                                                                />
                                                            </div>

                                                            <div className="grid gap-1">
                                                                <Label htmlFor="simple_entry_date">
                                                                    Data
                                                                </Label>
                                                                <Input
                                                                    id="simple_entry_date"
                                                                    type="date"
                                                                    value={simpleEntryForm.data.entry_date}
                                                                    onChange={(event) =>
                                                                        simpleEntryForm.setData(
                                                                            'entry_date',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={simpleEntryForm.errors.entry_date}
                                                                />
                                                            </div>

                                                            <div className="flex gap-2">
                                                                <Button
                                                                    type="submit"
                                                                    disabled={simpleEntryForm.processing}
                                                                >
                                                                    Salvar
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={cancelEditingSimpleEntry}
                                                                >
                                                                    Cancelar
                                                                </Button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                ) : (
                                                    <>
                                                        <td className="px-2 py-3">{entry.description}</td>
                                                        <td className="px-2 py-3">
                                                            {entry.entry_type === 'source' ? (
                                                                <Badge variant="secondary">
                                                                    Saida por fonte
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="outline">
                                                                    Saida simples
                                                                </Badge>
                                                            )}
                                                            {entry.expense_source?.type && (
                                                                <span className="ml-2 text-xs text-muted-foreground">
                                                                    {sourceTypeLabelByValue[entry.expense_source.type] ??
                                                                        entry.expense_source.type}
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            <Badge variant="outline">
                                                                {entry.expense_category?.name ?? 'Sem categoria'}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            {currencyFormatter.format(Number(entry.amount))}
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            {formatEntryDate(entry.entry_date)}
                                                        </td>
                                                        <td className="px-2 py-3">
                                                            <div className="flex gap-2">
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        startEditingSimpleEntry(entry)
                                                                    }
                                                                >
                                                                    Editar
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="destructive"
                                                                    onClick={() =>
                                                                        deleteSimpleEntry(entry.id, entry.entry_type)
                                                                    }
                                                                >
                                                                    Excluir
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </>
                                                )}
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
