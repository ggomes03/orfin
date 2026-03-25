import { router, usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const page = usePage();
    const currentYear = new Date().getFullYear();
    const exerciseYear = page.props.exerciseYear;
    const selectedYear = Number(exerciseYear?.selected ?? currentYear);
    const yearOptions = Array.isArray(exerciseYear?.options) && exerciseYear.options.length > 0
        ? exerciseYear.options
        : [selectedYear];

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2 overflow-hidden">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex items-center gap-2">
                <label htmlFor="exercise_year" className="text-xs text-muted-foreground">
                    Exercicio
                </label>
                <select
                    id="exercise_year"
                    value={selectedYear}
                    onChange={(event) => {
                        router.put(
                            '/financeiro/exercicio',
                            { exercise_year: Number(event.target.value) },
                            { preserveState: true, preserveScroll: true },
                        );
                    }}
                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:outline-none"
                >
                    {yearOptions.map((year) => (
                        <option key={year} value={year}>
                            {year}
                        </option>
                    ))}
                </select>
            </div>
        </header>
    );
}
