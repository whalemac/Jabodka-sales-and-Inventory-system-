<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('super.subscriptions.index', [
            'subscriptions' => Subscription::query()->with('user')->latest()->get(),
            'admins' => User::query()->where('role', 'admin')->orderBy('username')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:active,expired,cancelled'],
            'terms_accepted' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $data['terms_accepted'] = $request->boolean('terms_accepted');
        Subscription::query()->create($data);

        return back()->with('status', 'Subscription saved.');
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'plan_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:active,expired,cancelled'],
            'terms_accepted' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);
        $data['terms_accepted'] = $request->boolean('terms_accepted');
        $subscription->update($data);

        return back()->with('status', 'Subscription updated.');
    }
}
