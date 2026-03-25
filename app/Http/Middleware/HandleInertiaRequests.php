<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $currentYear = now()->year;
        $selectedExerciseYear = (int) $request->session()->get('exercise_year', $currentYear);

        if ($selectedExerciseYear < 1970 || $selectedExerciseYear > $currentYear) {
            $selectedExerciseYear = $currentYear;
        }

        $minimumYearFromData = null;

        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $minimumIncomeDate = $user->incomeEntries()->min('entry_date');
            $minimumExpenseDate = $user->expenseEntries()->min('entry_date');

            $years = collect([$minimumIncomeDate, $minimumExpenseDate])
                ->filter()
                ->map(fn (string $date): int => (int) substr($date, 0, 4));

            if ($years->isNotEmpty()) {
                $minimumYearFromData = (int) $years->min();
            }
        }

        $startYear = max(1970, min($currentYear - 10, $selectedExerciseYear, $minimumYearFromData ?? $currentYear));
        $exerciseYearOptions = collect(range($startYear, $currentYear))
            ->reverse()
            ->values();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'exerciseYear' => [
                'selected' => $selectedExerciseYear,
                'options' => $exerciseYearOptions,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
