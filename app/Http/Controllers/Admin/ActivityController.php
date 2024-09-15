<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Http\Requests\GetActivitiesByDateRangeRequest;
use App\Models\Activity;
use App\Services\ActivityService;
use BalajiDharma\LaravelFormBuilder\FormBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    protected $activityService;
    protected $title = 'Activities';

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $activities = $this->activityService->getGlobalActivities();
        return view('admin.activity.index', compact('activities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(FormBuilder $formBuilder): View
    {
        $form = $formBuilder->create(\App\Forms\Admin\ActivityForm::class, [
            'method' => 'POST',
            'url' => route('admin.activity.store'),
        ]);
        $title = $this->title;

        return view('admin.form.edit', compact('form', 'title'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ActivityRequest $request): RedirectResponse
    {
        $activityCount = Activity::where("is_global", 1)->whereDate('date', $request->date)->count();

        if ($activityCount >= 4) {
            return redirect()->route('admin.activity.index')
                ->with('message', 'You have reached the maximum limit of 4 activities for this day.');
        }

        $this->activityService->storeActivity($request->all());

        return redirect()->route('admin.activity.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FormBuilder $formBuilder,Activity $activity): View
    {
        $form = $formBuilder->create(\App\Forms\Admin\ActivityForm::class, [
            'method' => 'PUT',
            'url' => route('admin.activity.update', $activity->id),
            'model' => $activity,
        ]);
        $title = $this->title;

        return view('admin.form.edit', compact('form', 'title'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActivityRequest $request, string $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);

        $this->activityService->updateActivity($activity, $request->all());

        return redirect()->route('admin.activity.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);

        $this->activityService->deleteActivity($activity);

        return redirect()->route('admin.activity.index');
    }

    public function getActivitiesByDateRange(GetActivitiesByDateRangeRequest $request) : JsonResponse {

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $user = Auth::user();

        $activities = $user->activities()
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get();

        return response()->json([
            'activities' => $activities
        ]);
    }
}
