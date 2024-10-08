<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use BalajiDharma\LaravelAdminCore\Actions\User\UserCreateAction;
use BalajiDharma\LaravelAdminCore\Actions\User\UserUpdateAction;
use BalajiDharma\LaravelAdminCore\Data\User\UserCreateData;
use BalajiDharma\LaravelAdminCore\Data\User\UserUpdateData;
use BalajiDharma\LaravelFormBuilder\FormBuilder;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    protected $title = 'Users';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $this->authorize('adminViewAny', User::class);
        $users = (new User)->newQuery();

        if (request()->has('search')) {
            $users->where('name', 'Like', '%'.request()->input('search').'%');
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $users->orderBy($attribute, $sort_order);
        } else {
            $users->latest();
        }

        $users = $users->paginate(config('admin.paginate.per_page'))
            ->onEachSide(config('admin.paginate.each_side'));

        return view('admin.user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(FormBuilder $formBuilder): View
    {
        $this->authorize('adminCreate', User::class);
        $form = $formBuilder->create(\App\Forms\Admin\UserForm::class, [
            'method' => 'POST',
            'url' => route('admin.user.store'),
        ]);
        $title = $this->title;

        return view('admin.form.edit', compact('form', 'title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserCreateData $data, UserCreateAction $userCreateAction): RedirectResponse
    {
        $this->authorize('adminCreate', User::class);
        $user = $userCreateAction->handle($data);

        $now = Carbon::today();

        $activitiesIds = Activity::where('is_global', 1)->where('date', '>=', $now)->pluck('id');
        $user->activities()->attach($activitiesIds);

        return redirect()->route('admin.user.index')
            ->with('message', __('User created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function show(User $user): View
    {
        $this->authorize('adminView', $user);
        $roles = Role::all();
        $userHasRoles = array_column(json_decode($user->roles, true), 'id');

        return view('admin.user.show', compact('user', 'roles', 'userHasRoles'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(User $user, FormBuilder $formBuilder): View
    {
        $this->authorize('adminUpdate', $user);

        $form = $formBuilder->create(\App\Forms\Admin\UserForm::class, [
            'method' => 'PUT',
            'url' => route('admin.user.update', $user->id),
            'model' => $user,
        ]);
        $title = $this->title;
        $activities = $user->activities()->paginate(10);

        return view('admin.form.editUser', compact('form', 'title', 'activities', 'user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserUpdateData $data, User $user, UserUpdateAction $userUpdateAction)
    {
        $this->authorize('adminUpdate', $user);
        $userUpdateAction->handle($data, $user);

        return redirect()->route('admin.user.index')
            ->with('message', __('User updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('adminDelete', $user);
        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('message', __('User deleted successfully'));
    }

    /**
     * Show the user a form to change their personal information & password.
     *
     * @return \Illuminate\View\View
     */
    public function accountInfo(): View
    {
        $user = \Auth::user();

        return view('admin.user.account_info', compact('user'));
    }

    /**
     * Save the modified personal information for a user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accountInfoStore(Request $request): RedirectResponse
    {
        $request->validateWithBag('account', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.\Auth::user()->id],
        ]);

        $user = \Auth::user()->update($request->except(['_token']));

        if ($user) {
            $message = 'Account updated successfully.';
        } else {
            $message = 'Error while saving. Please try again.';
        }

        return redirect()->route('admin.account.info')->with('account_message', __($message));
    }

    /**
     * Save the new password for a user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePasswordStore(Request $request): RedirectResponse
    {
        $validator = \Validator::make($request->all(), [
            'old_password' => ['required'],
            'new_password' => ['required', Rules\Password::defaults()],
            'confirm_password' => ['required', 'same:new_password', Rules\Password::defaults()],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->failed()) {
                return;
            }
            if (! Hash::check($request->input('old_password'), \Auth::user()->password)) {
                $validator->errors()->add(
                    'old_password', __('Old password is incorrect.')
                );
            }
        });

        $validator->validateWithBag('password');

        $user = \Auth::user()->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        if ($user) {
            $message = 'Password updated successfully.';
        } else {
            $message = 'Error while saving. Please try again.';
        }

        return redirect()->route('admin.account.info')->with('password_message', __($message));
    }
}
