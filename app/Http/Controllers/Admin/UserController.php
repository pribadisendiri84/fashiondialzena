<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize(Ability::ManageUsers->value);

        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize(Ability::ManageUsers->value);

        $data = $this->validated($request);

        User::query()->create($data);

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorize(Ability::ManageUsers->value);

        $data = $this->validated($request, $user);

        if ($this->wouldRemoveLastOwner($user, $data['role'])) {
            return back()->withErrors(['Minimal satu owner harus tersisa.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorize(Ability::ManageUsers->value);

        if ($user->is($this->currentUser())) {
            return back()->withErrors(['Tidak bisa menghapus akun sendiri.']);
        }

        if ($this->wouldRemoveLastOwner($user, UserRole::Staff)) {
            return back()->withErrors(['Minimal satu owner harus tersisa.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $data['role'] = UserRole::from($data['role']);

        return $data;
    }

    private function wouldRemoveLastOwner(User $user, UserRole $nextRole): bool
    {
        if (! $user->isOwner() || $nextRole === UserRole::Owner) {
            return false;
        }

        return ! User::query()
            ->where('role', UserRole::Owner)
            ->where('id', '!=', $user->id)
            ->exists();
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}