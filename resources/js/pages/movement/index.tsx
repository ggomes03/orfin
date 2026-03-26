import { Head, useForm } from '@inertiajs/react';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentacao',
        href: '/financeiro/movimentacao',
    },
];

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

type IncomeSource = {
    id: number;
    type: string;
    description: string;
    monthly_amount: string;
};

type ExpenseSource = {
    id: number;
    type: string;
    description: string;
    category_id?: number | null;
    monthly_amount: string | null;
    monthly_amount_started_at?: string | null;
    expense_category?: {
        id: number;
        name: string;
    } | null;
    amount_histories?: {
        amount: string;
        effective_from: string;
    }[];
};

type IncomeEntry = {
    id: number;
    entry_type: 'source' | 'simple';
    description: string;
    amount: string;
    entry_date: string;
    income_source?: {
        type: string;
        description: string;
    } | null;
};

type ExpenseEntry = {
    id: number;
    entry_type: 'source' | 'simple';
    description: string;
    category_id?: number | null;
    amount: string;
    entry_date: string;
    expense_source_id?: number | null;
    expense_source?: {
        type: string;
        description: string;
    } | null;
    expense_category?: {
        id: number;
        name: string;
    } | null;
};

type ExpenseCategoryOption = {
    value: string;
    label: string;
};

export default function MovementPage({
    exerciseYear,
    incomeSources,
    expenseSources,
    fixedExpenseSources,
    incomeEntries,
    expenseEntries,
    expenseCategoryOptions,
    status,
}: {
    exerciseYear: number;
    incomeSources: IncomeSource[];
    expenseSources: ExpenseSource[];
    fixedExpenseSources: ExpenseSource[];
    incomeEntries: IncomeEntry[];
    expenseEntries: ExpenseEntry[];
    expenseCategoryOptions: ExpenseCategoryOption[];
    status?: string;
}) {
    const getTodayDateInputValue = () => {
        const now = new Date();
        const year = exerciseYear;
        const month = `${now.getMonth() + 1}`.padStart(2, '0');
        const day = `${now.getDate()}`.padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const [isIncomeModalOpen, setIsIncomeModalOpen] = useState(false);
    const [isExpenseModalOpen, setIsExpenseModalOpen] = useState(false);
    const [isExpenseEditModalOpen, setIsExpenseEditModalOpen] = useState(false);
    const [isFixedSourceEditModalOpen, setIsFixedSourceEditModalOpen] = useState(false);
    const [editingExpenseEntry, setEditingExpenseEntry] = useState<ExpenseEntry | null>(null);
    const [editingFixedSource, setEditingFixedSource] = useState<ExpenseSource | null>(null);
    const [selectedMonthFilter, setSelectedMonthFilter] = useState<string>('all');

    const incomeForm = useForm({
        entry_mode: (incomeSources.length > 0 ? 'source' : 'simple') as 'source' | 'simple',
        income_source_id: incomeSources[0]?.id ? String(incomeSources[0].id) : '',
        description: '',
        amount: '',
        entry_date: getTodayDateInputValue(),
    });

    const expenseForm = useForm({
        entry_mode: (expenseSources.length > 0 ? 'source' : 'simple') as 'source' | 'simple' | 'fixed',
        expense_source_id: expenseSources[0]?.id ? String(expenseSources[0].id) : '',
        description: '',
        category_id: expenseCategoryOptions[0]?.value ?? '',
        amount: '',
        entry_date: getTodayDateInputValue(),
        effective_from: getTodayDateInputValue(),
    });

    const expenseEditForm = useForm({
        description: '',
        category_id: '',
        amount: '',
        entry_date: '',
        redirect_to: 'movement',
    });

    const fixedSourceEditForm = useForm({
        description: '',
        category_id: '',
        monthly_amount: '',
        effective_from: '',
        redirect_to: 'movement',
    });

    const currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

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

    const getMonthFromEntryDate = (value: string): number | null => {
        const isoDateMatch = value.match(/^\d{4}-(\d{2})-\d{2}/);

        if (!isoDateMatch) {
            return null;
        }

        return Number(isoDateMatch[1]);
    };

    const filteredIncomeEntries = useMemo(() => {
        if (selectedMonthFilter === 'all') {
            return incomeEntries;
        }

        const selectedMonthNumber = Number(selectedMonthFilter);

        return incomeEntries.filter((entry) => getMonthFromEntryDate(entry.entry_date) === selectedMonthNumber);
    }, [incomeEntries, selectedMonthFilter]);

    const filteredExpenseEntries = useMemo(() => {
        if (selectedMonthFilter === 'all') {
            return expenseEntries;
        }

        const selectedMonthNumber = Number(selectedMonthFilter);

        return expenseEntries.filter((entry) => getMonthFromEntryDate(entry.entry_date) === selectedMonthNumber);
    }, [expenseEntries, selectedMonthFilter]);

    const submitIncome = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        incomeForm.post('/financeiro/movimentacao/entradas', {
            preserveScroll: true,
            onSuccess: () => {
                incomeForm.reset('description', 'amount');
                setIsIncomeModalOpen(false);
            },
        });
    };

    const submitExpense = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        expenseForm.post('/financeiro/movimentacao/saidas', {
            preserveScroll: true,
            onSuccess: () => {
                expenseForm.reset('description', 'amount');
                expenseForm.setData('effective_from', getTodayDateInputValue());
                setIsExpenseModalOpen(false);
            },
        });
    };

    const startEditingExpenseEntry = (entry: ExpenseEntry) => {
        setEditingExpenseEntry(entry);
        expenseEditForm.setData('description', entry.description);
        expenseEditForm.setData('category_id', entry.category_id ? String(entry.category_id) : '');
        expenseEditForm.setData('amount', entry.amount);
        expenseEditForm.setData('entry_date', normalizeDateInputValue(entry.entry_date));
        expenseEditForm.clearErrors();
        setIsExpenseEditModalOpen(true);
    };

    const submitExpenseEdit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!editingExpenseEntry) {
            return;
        }

        const updatePath = editingExpenseEntry.entry_type === 'source'
            ? `/financeiro/saidas-fonte/${editingExpenseEntry.id}`
            : `/financeiro/saidas-avulsas/${editingExpenseEntry.id}`;

        expenseEditForm.patch(updatePath, {
            preserveScroll: true,
            onSuccess: () => {
                setIsExpenseEditModalOpen(false);
                setEditingExpenseEntry(null);
                expenseEditForm.clearErrors();
            },
        });
    };

    const startEditingFixedSource = (source: ExpenseSource) => {
        setEditingFixedSource(source);
        fixedSourceEditForm.setData('description', source.description);
        fixedSourceEditForm.setData('category_id', source.category_id ? String(source.category_id) : '');
        fixedSourceEditForm.setData('monthly_amount', source.monthly_amount ?? '');
        fixedSourceEditForm.setData('effective_from', normalizeDateInputValue(source.monthly_amount_started_at ?? ''));
        fixedSourceEditForm.clearErrors();
        setIsFixedSourceEditModalOpen(true);
    };

    const submitFixedSourceEdit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!editingFixedSource) {
            return;
        }

        fixedSourceEditForm.patch(`/financeiro/fontes-saida-fixas/${editingFixedSource.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsFixedSourceEditModalOpen(false);
                setEditingFixedSource(null);
                fixedSourceEditForm.clearErrors();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Movimentacao" />

            <Dialog open={isExpenseEditModalOpen} onOpenChange={setIsExpenseEditModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Editar saida</DialogTitle>
                    </DialogHeader>
                    <form className="space-y-4" onSubmit={submitExpenseEdit}>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_expense_description">Descricao</Label>
                            <Input
                                id="edit_expense_description"
                                value={expenseEditForm.data.description}
                                onChange={(event) => expenseEditForm.setData('description', event.target.value)}
                            />
                            <InputError message={expenseEditForm.errors.description} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_expense_category">Categoria</Label>
                            <select
                                id="edit_expense_category"
                                value={expenseEditForm.data.category_id}
                                onChange={(event) => expenseEditForm.setData('category_id', event.target.value)}
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <option value="">Selecione uma categoria</option>
                                {expenseCategoryOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={expenseEditForm.errors.category_id} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_expense_amount">Valor</Label>
                            <Input
                                id="edit_expense_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value={expenseEditForm.data.amount}
                                onChange={(event) => expenseEditForm.setData('amount', event.target.value)}
                            />
                            <InputError message={expenseEditForm.errors.amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_expense_date">Data</Label>
                            <Input
                                id="edit_expense_date"
                                type="date"
                                value={expenseEditForm.data.entry_date}
                                onChange={(event) => expenseEditForm.setData('entry_date', event.target.value)}
                            />
                            <InputError message={expenseEditForm.errors.entry_date} />
                        </div>
                        <Button type="submit" variant="destructive" disabled={expenseEditForm.processing}>Salvar alteracoes</Button>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isFixedSourceEditModalOpen} onOpenChange={setIsFixedSourceEditModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Editar saida fixa</DialogTitle>
                    </DialogHeader>
                    <form className="space-y-4" onSubmit={submitFixedSourceEdit}>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_fixed_description">Descricao</Label>
                            <Input
                                id="edit_fixed_description"
                                value={fixedSourceEditForm.data.description}
                                onChange={(event) => fixedSourceEditForm.setData('description', event.target.value)}
                            />
                            <InputError message={fixedSourceEditForm.errors.description} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_fixed_category">Categoria</Label>
                            <select
                                id="edit_fixed_category"
                                value={fixedSourceEditForm.data.category_id}
                                onChange={(event) => fixedSourceEditForm.setData('category_id', event.target.value)}
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <option value="">Selecione uma categoria</option>
                                {expenseCategoryOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={fixedSourceEditForm.errors.category_id} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_fixed_amount">Valor mensal</Label>
                            <Input
                                id="edit_fixed_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value={fixedSourceEditForm.data.monthly_amount}
                                onChange={(event) => fixedSourceEditForm.setData('monthly_amount', event.target.value)}
                            />
                            <InputError message={fixedSourceEditForm.errors.monthly_amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit_fixed_effective_from">Vigente a partir de</Label>
                            <Input
                                id="edit_fixed_effective_from"
                                type="date"
                                value={fixedSourceEditForm.data.effective_from}
                                onChange={(event) => fixedSourceEditForm.setData('effective_from', event.target.value)}
                            />
                            <InputError message={fixedSourceEditForm.errors.effective_from} />
                        </div>
                        <Button type="submit" variant="destructive" disabled={fixedSourceEditForm.processing}>Salvar alteracoes</Button>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="rounded-lg border border-sidebar-border/70 bg-background px-4 py-2 text-sm text-muted-foreground dark:border-sidebar-border">
                    Exercicio selecionado: <span className="font-semibold text-foreground">{exerciseYear}</span>
                </div>

                <div className="grid gap-2 md:max-w-xs">
                    <Label htmlFor="movement_month_filter">Filtrar por mes</Label>
                    <select
                        id="movement_month_filter"
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

                {status && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {status}
                    </div>
                )}

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4 space-y-0">
                            <div>
                                <CardTitle>Entradas</CardTitle>
                                <CardDescription>
                                    Historico das entradas no exercicio.
                                </CardDescription>
                            </div>
                            <Dialog open={isIncomeModalOpen} onOpenChange={setIsIncomeModalOpen}>
                                <DialogTrigger asChild>
                                    <Button>Nova entrada</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Cadastrar entrada</DialogTitle>
                                        <DialogDescription>
                                            Use entrada por fonte para receitas recorrentes ou entrada simples para lancamentos avulsos.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form className="space-y-4" onSubmit={submitIncome}>
                                        <div className="grid gap-2">
                                            <Label htmlFor="income_entry_mode">Tipo de entrada</Label>
                                            <select
                                                id="income_entry_mode"
                                                value={incomeForm.data.entry_mode}
                                                onChange={(event) => {
                                                    const mode = event.target.value as 'source' | 'simple';
                                                    incomeForm.setData('entry_mode', mode);
                                                }}
                                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                <option value="source">Entrada por fonte</option>
                                                <option value="simple">Entrada simples</option>
                                            </select>
                                            <InputError message={incomeForm.errors.entry_mode} />
                                        </div>

                                        {incomeForm.data.entry_mode === 'source' ? (
                                            <div className="grid gap-2">
                                                <Label htmlFor="income_source_id">Fonte de renda</Label>
                                                <select
                                                    id="income_source_id"
                                                    value={incomeForm.data.income_source_id}
                                                    onChange={(event) =>
                                                        incomeForm.setData('income_source_id', event.target.value)
                                                    }
                                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                                >
                                                    <option value="">Selecione uma fonte</option>
                                                    {incomeSources.map((source) => (
                                                        <option key={source.id} value={String(source.id)}>
                                                            {source.description}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError message={incomeForm.errors.income_source_id} />
                                            </div>
                                        ) : (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="income_description">Descricao</Label>
                                                    <Input
                                                        id="income_description"
                                                        value={incomeForm.data.description}
                                                        onChange={(event) =>
                                                            incomeForm.setData('description', event.target.value)
                                                        }
                                                    />
                                                    <InputError message={incomeForm.errors.description} />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="income_amount">Valor</Label>
                                                    <Input
                                                        id="income_amount"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={incomeForm.data.amount}
                                                        onChange={(event) =>
                                                            incomeForm.setData('amount', event.target.value)
                                                        }
                                                    />
                                                    <InputError message={incomeForm.errors.amount} />
                                                </div>
                                            </>
                                        )}

                                        <div className="grid gap-2">
                                            <Label htmlFor="income_entry_date">Data</Label>
                                            <Input
                                                id="income_entry_date"
                                                type="date"
                                                value={incomeForm.data.entry_date}
                                                onChange={(event) => incomeForm.setData('entry_date', event.target.value)}
                                            />
                                            <InputError message={incomeForm.errors.entry_date} />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={
                                                incomeForm.processing ||
                                                (incomeForm.data.entry_mode === 'source' && incomeSources.length === 0)
                                            }
                                        >
                                            Salvar entrada
                                        </Button>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </CardHeader>
                        <CardContent>
                            {filteredIncomeEntries.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    {selectedMonthFilter === 'all'
                                        ? 'Nenhuma entrada cadastrada.'
                                        : 'Nenhuma entrada encontrada para o mes selecionado.'}
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[620px] text-sm">
                                        <thead>
                                            <tr className="border-b text-left text-muted-foreground">
                                                <th className="px-2 py-2 font-medium">Descricao</th>
                                                <th className="px-2 py-2 font-medium">Tipo</th>
                                                <th className="px-2 py-2 font-medium">Valor</th>
                                                <th className="px-2 py-2 font-medium">Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredIncomeEntries.map((entry) => (
                                                <tr key={entry.id} className="border-b last:border-0">
                                                    <td className="px-2 py-3">{entry.description}</td>
                                                    <td className="px-2 py-3">
                                                        {entry.entry_type === 'source' ? (
                                                            <Badge variant="secondary">Entrada por fonte</Badge>
                                                        ) : (
                                                            <Badge variant="outline">Entrada simples</Badge>
                                                        )}
                                                    </td>
                                                    <td className="px-2 py-3">{currencyFormatter.format(Number(entry.amount))}</td>
                                                    <td className="px-2 py-3">{formatEntryDate(entry.entry_date)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4 space-y-0">
                            <div>
                                <CardTitle>Saidas</CardTitle>
                                <CardDescription>
                                    Historico das saidas no exercicio.
                                </CardDescription>
                            </div>
                            <Dialog open={isExpenseModalOpen} onOpenChange={setIsExpenseModalOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="destructive">Nova saida</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Cadastrar saida</DialogTitle>
                                        <DialogDescription>
                                            Use o mesmo formulario para saida por fonte, saida simples e saida fixa.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form className="space-y-4" onSubmit={submitExpense}>
                                        <div className="grid gap-2">
                                            <Label htmlFor="expense_entry_mode">Tipo de saida</Label>
                                            <select
                                                id="expense_entry_mode"
                                                value={expenseForm.data.entry_mode}
                                                onChange={(event) => {
                                                    const mode = event.target.value as 'source' | 'simple' | 'fixed';
                                                    expenseForm.setData('entry_mode', mode);
                                                }}
                                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                <option value="source">Saida por fonte</option>
                                                <option value="simple">Saida simples</option>
                                                <option value="fixed">Saida fixa</option>
                                            </select>
                                            <InputError message={expenseForm.errors.entry_mode} />
                                        </div>

                                        {expenseForm.data.entry_mode === 'source' ? (
                                            <div className="grid gap-2">
                                                <Label htmlFor="expense_source_id">Fonte de saida</Label>
                                                <select
                                                    id="expense_source_id"
                                                    value={expenseForm.data.expense_source_id}
                                                    onChange={(event) =>
                                                        expenseForm.setData('expense_source_id', event.target.value)
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
                                                <InputError message={expenseForm.errors.expense_source_id} />
                                            </div>
                                        ) : expenseForm.data.entry_mode === 'fixed' ? (
                                            <div className="grid gap-2">
                                                <Label htmlFor="expense_description">Descricao da conta fixa</Label>
                                                <Input
                                                    id="expense_description"
                                                    value={expenseForm.data.description}
                                                    onChange={(event) =>
                                                        expenseForm.setData('description', event.target.value)
                                                    }
                                                />
                                                <InputError message={expenseForm.errors.description} />
                                            </div>
                                        ) : (
                                            <div className="grid gap-2">
                                                <Label htmlFor="expense_description">Descricao</Label>
                                                <Input
                                                    id="expense_description"
                                                    value={expenseForm.data.description}
                                                    onChange={(event) =>
                                                        expenseForm.setData('description', event.target.value)
                                                    }
                                                />
                                                <InputError message={expenseForm.errors.description} />
                                            </div>
                                        )}

                                        <div className="grid gap-2">
                                            <Label htmlFor="expense_category">Categoria</Label>
                                            <select
                                                id="expense_category"
                                                value={expenseForm.data.category_id}
                                                onChange={(event) => expenseForm.setData('category_id', event.target.value)}
                                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                <option value="">Selecione uma categoria</option>
                                                {expenseCategoryOptions.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={expenseForm.errors.category_id} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="expense_amount">
                                                {expenseForm.data.entry_mode === 'fixed' ? 'Valor mensal' : 'Valor'}
                                            </Label>
                                            <Input
                                                id="expense_amount"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={expenseForm.data.amount}
                                                onChange={(event) => expenseForm.setData('amount', event.target.value)}
                                            />
                                            <InputError message={expenseForm.errors.amount} />
                                        </div>

                                        {expenseForm.data.entry_mode === 'fixed' ? (
                                            <div className="grid gap-2">
                                                <Label htmlFor="expense_effective_from">Vigente a partir de</Label>
                                                <Input
                                                    id="expense_effective_from"
                                                    type="date"
                                                    value={expenseForm.data.effective_from}
                                                    onChange={(event) => expenseForm.setData('effective_from', event.target.value)}
                                                />
                                                <InputError message={expenseForm.errors.effective_from} />
                                            </div>
                                        ) : (
                                            <div className="grid gap-2">
                                                <Label htmlFor="expense_entry_date">Data</Label>
                                                <Input
                                                    id="expense_entry_date"
                                                    type="date"
                                                    value={expenseForm.data.entry_date}
                                                    onChange={(event) => expenseForm.setData('entry_date', event.target.value)}
                                                />
                                                <InputError message={expenseForm.errors.entry_date} />
                                            </div>
                                        )}

                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={
                                                expenseForm.processing ||
                                                (expenseForm.data.entry_mode === 'source' && expenseSources.length === 0)
                                            }
                                        >
                                            {expenseForm.data.entry_mode === 'fixed' ? 'Salvar saida fixa' : 'Salvar saida'}
                                        </Button>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-5">
                                <p className="mb-2 text-sm font-medium text-muted-foreground">Saidas fixas vigentes</p>
                                {fixedExpenseSources.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Nenhuma saida fixa cadastrada.</p>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[700px] text-sm">
                                            <thead>
                                                <tr className="border-b text-left text-muted-foreground">
                                                    <th className="px-2 py-2 font-medium">Descricao</th>
                                                    <th className="px-2 py-2 font-medium">Categoria</th>
                                                    <th className="px-2 py-2 font-medium">Valor mensal</th>
                                                    <th className="px-2 py-2 font-medium">Vigente desde</th>
                                                    <th className="px-2 py-2 font-medium">Acoes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {fixedExpenseSources.map((source) => (
                                                    <tr key={source.id} className="border-b last:border-0">
                                                        <td className="px-2 py-3">{source.description}</td>
                                                        <td className="px-2 py-3">
                                                            <Badge variant="outline">
                                                                {source.expense_category?.name ?? 'Sem categoria'}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-2 py-3">{currencyFormatter.format(Number(source.monthly_amount ?? 0))}</td>
                                                        <td className="px-2 py-3">{source.monthly_amount_started_at ? formatEntryDate(source.monthly_amount_started_at) : '-'}</td>
                                                        <td className="px-2 py-3">
                                                            <Button type="button" variant="outline" onClick={() => startEditingFixedSource(source)}>
                                                                Editar
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>

                            {filteredExpenseEntries.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    {selectedMonthFilter === 'all'
                                        ? 'Nenhuma saida cadastrada.'
                                        : 'Nenhuma saida encontrada para o mes selecionado.'}
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[680px] text-sm">
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
                                                    <td className="px-2 py-3">{entry.description}</td>
                                                    <td className="px-2 py-3">
                                                        {entry.entry_type === 'source' ? (
                                                            <Badge variant="secondary">Saida por fonte</Badge>
                                                        ) : (
                                                            <Badge variant="outline">Saida simples</Badge>
                                                        )}
                                                    </td>
                                                    <td className="px-2 py-3">
                                                        <Badge variant="outline">
                                                            {entry.expense_category?.name ?? 'Sem categoria'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-2 py-3">{currencyFormatter.format(Number(entry.amount))}</td>
                                                    <td className="px-2 py-3">{formatEntryDate(entry.entry_date)}</td>
                                                    <td className="px-2 py-3">
                                                        <Button type="button" variant="outline" onClick={() => startEditingExpenseEntry(entry)}>
                                                            Editar
                                                        </Button>
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
            </div>
        </AppLayout>
    );
}
