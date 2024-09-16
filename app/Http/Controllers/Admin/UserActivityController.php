<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityService;
use BalajiDharma\LaravelFormBuilder\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class UserActivityController extends Controller
{
    protected $activityService;
    protected $title = 'User Activities';

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(User $user,FormBuilder $formBuilder): View
    {
        $form = $formBuilder->create(\App\Forms\Admin\ActivityForm::class, [
            'method' => 'POST',
            'url' => route('admin.user.activity.store', $user->id)
        ]);
        $title = $this->title;

        return view('admin.form.edit', compact('form', 'title', 'user'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(User $user, ActivityRequest $request): RedirectResponse
    {
         $activityCount = $user->activities()->whereDate('date', $request->date)->count();
         $data = $request->all();

        if ($activityCount >= 4) {
            return redirect()->route('admin.user.edit', $user->id)
                ->with('message', 'You have reached the maximum limit of 4 activities for this day.');
        }


        $this->activityService->storeUserActivity($user, $request->all(), false);

        // $imageUrl = null;
        // if (isset($data['image_url']) && $data['image_url']->isValid()) {
        //     $image = $data['image_url'];
        //     $imageUrl = $image->store('images', 'public');
        // }

        // $activity = Activity::create([
        //     'title' => $data['title'],
        //     'description' => $data['description'],
        //     'image_url' => $imageUrl,
        //     'date' => $data['date'],
        //     'is_global' => 0,
        // ]);
        // $activity->users()->attach($user->id);

        return redirect()->route('admin.user.edit', $user->id);
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
    public function edit(User $user, Activity $activity, FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(\App\Forms\Admin\ActivityForm::class, [
            'method' => 'PUT',
            'url' => route('admin.user.activity.update', [$user->id, $activity->id]),
            'model' => $activity,
        ]);
        $title = $this->title;

        return view('admin.form.edit', compact('form', 'title', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(User $user, ActivityRequest $request, string $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);
        if($activity->is_global){
            $this->activityService->createUserActivity($user, $activity, $request->all());
            
        }else{
            $this->activityService->updateActivity($activity, $request->all());
        }

        return redirect()->route('admin.user.edit', $user->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, string $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);
        $user->activities()->detach($activity->id);
        if(!$activity->is_global){
            $this->activityService->deleteActivity($activity);
        }
        return redirect()->route('admin.user.edit', $user->id);
    }
}
