<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateExerciseYearRequest;
use Illuminate\Http\RedirectResponse;

class ExerciseYearController extends Controller
{
    public function update(UpdateExerciseYearRequest $request): RedirectResponse
    {
        $request->session()->put('exercise_year', $request->integer('exercise_year'));

        return back();
    }
}
