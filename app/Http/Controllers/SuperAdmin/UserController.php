<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('super.users.index', [
            'users' => User::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('super.users.form', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = User::query()->create($data);
        ActivityLogger::log('user.create', $user, 'Account created by Super Admin');

        return redirect()->route('super.users.index')->with('status', 'Account created.');
    }

    public function edit(User $user): View
    {
        return view('super.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        ActivityLogger::log('user.update', $user, 'Account updated by Super Admin');

        return redirect()->route('super.users.index')->with('status', 'Account updated.');
    }

    protected function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ]);
    }
}
