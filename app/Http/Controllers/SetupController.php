<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSetupRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View
    {
        return view('auth.setup', ['configured' => $this->isConfigured()]);
    }

    public function store(StoreSetupRequest $request): RedirectResponse
    {
        abort_if($this->isConfigured(), 409, 'تم إنشاء حساب الإدارة العليا مسبقاً.');

        $data = $request->validated();

        $role = Role::query()->where('code', 'super_admin')->firstOrFail();
        User::query()->create([
            'role_id' => $role->id,
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return redirect()->route('login')->with('status', 'تم إنشاء حساب الإدارة العليا بنجاح.');
    }

    private function isConfigured(): bool
    {
        return User::query()->whereHas('role', fn ($query) => $query->where('code', 'super_admin'))->exists();
    }
}
