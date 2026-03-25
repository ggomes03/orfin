<?php

namespace App\Http\Controllers;

use App\Application\UseCases\GetDashboardDataUseCase;
use App\Http\Requests\DashboardRequest;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request, GetDashboardDataUseCase $getDashboardDataUseCase): Response
    {
        /** @var User $user */
        $user = $request->user();
        $currentYear = now()->year;
        $exerciseYear = (int) $request->session()->get('exercise_year', $currentYear);

        if ($exerciseYear < 1970 || $exerciseYear > $currentYear) {
            $exerciseYear = $currentYear;
        }

        $dashboardData = $getDashboardDataUseCase->execute(
            user: $user,
            exerciseYear: $exerciseYear,
            selectedMonth: $request->selectedMonth(),
        );

        return Inertia::render('dashboard', $dashboardData);
    }
}
