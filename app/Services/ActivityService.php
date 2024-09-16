<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ActivityService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllActivities()
    {
        return Activity::query()->paginate(10);
    }

    public function getGlobalActivities()
    {
        return Activity::where("is_global", 1)->paginate(10);
    }

    public function storeActivity(array $data, bool $is_global = true)
    {
        $imageUrl = null;
        if (isset($data['image_url']) && $data['image_url']->isValid()) {
            $image = $data['image_url'];
            $imageUrl = $image->store('images', 'public');
        }

        $activity = Activity::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'image_url' => $imageUrl,
            'date' => $data['date'],
            'is_global' => $is_global,
        ]);
            $this->attachActivityToAllUsers($activity);
    }

    public function storeUserActivity(User $user, array $data, bool $is_global = true)
    {
        $imageUrl = null;
        if (isset($data['image_url']) && $data['image_url']->isValid()) {
            $image = $data['image_url'];
            $imageUrl = $image->store('images', 'public');
        }

        $activity = Activity::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'image_url' => $imageUrl,
            'date' => $data['date'],
            'is_global' => 0,
        ]);
        $activity->users()->attach($user->id);
    }

    public function attachActivityToAllUsers(Activity $activity)
    {
        
        $userIds = User::whereDoesntHave('roles', function($query) {
            $query->where('name', config('admin.roles.super_admin'));
        })->pluck('id');
        $activity->users()->attach($userIds);
    }

    public function updateActivity(Activity $activity, array $data)
    {
        $imageUrl = $activity->image_url;
        if (isset($data['image']) && $data['image']->isValid()) {
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            $image = $data['image'];
            $imageUrl = $image->store('images', 'public');
        }

        $activity->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'image_url' => $imageUrl,
            'date' => $data['date'],
        ]);
    }

    public function createUserActivity(User $user, Activity $activity, array $data)
    {
        $imageUrl = $activity->image_url;
        if (isset($data['image']) && $data['image']->isValid()) {
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            $image = $data['image'];
            $imageUrl = $image->store('images', 'public');
        }

        $activity_new = Activity::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'image_url' => $imageUrl,
            'date' => $data['date'],
            'is_global' => 0,
        ]);
        $activity->users()->updateExistingPivot($user->id, ['activity_id' => $activity_new->id]);
        $activity->save();
    }

    public function deleteActivity(Activity $activity)
    {
        if ($activity->image_url) {
            Storage::disk('public')->delete($activity->image_url);
        }

        $activity->delete();
    }
}
