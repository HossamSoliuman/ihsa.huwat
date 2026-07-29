<?php

namespace App\Http\Controllers;

use App\Models\EmploymentJob;
use Illuminate\View\View;

class PublicJobController extends Controller
{
    public function index(): View
    {
        $jobs = EmploymentJob::query()->open()->with('port')->latest('published_at')->latest('id')->get();

        return view('employment.jobs.index', compact('jobs'));
    }

    public function show(EmploymentJob $job): View
    {
        abort_unless(EmploymentJob::query()->open()->whereKey($job)->exists(), 404);

        return view('employment.jobs.show', ['job' => $job->load('port')]);
    }
}
