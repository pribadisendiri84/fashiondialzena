<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Enums\UserRole;
use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $this->authorize(Ability::ManageUsers->value);

        $query = $this->applyTrashFilter(User::query()->orderBy('name'), $request);

        return view('admin.users.index', [
            'users' => $query->get(),
            'roles' => UserRole::cases(),
            ...$this->trashViewData(User::class, $request),
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

        if ($this->wouldRemoveLastProtected($user, $data['role'])) {
            return back()->withErrors(['Minimal satu superadmin dan satu admin harus tersisa.']);
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

        if ($this->wouldRemoveLastProtected($user, UserRole::Staff)) {
            return back()->withErrors(['Minimal satu superadmin dan satu admin harus tersisa.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna dihapus.');
    }

    public function restore(User $user)
    {
        $this->authorize(Ability::ManageUsers->value);
        $user->restore();

        return redirect()->route('admin.users.index', ['trashed' => 1])->with('ok', 'Pengguna dipulihkan.');
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

    private function wouldRemoveLastProtected(User $user, UserRole $nextRole): bool
    {
        if ($user->isSuperadmin() && $nextRole !== UserRole::Superadmin) {
            return ! User::query()
                ->where('role', UserRole::Superadmin)
                ->where('id', '!=', $user->id)
                ->exists();
        }

        if ($user->isAdmin() && $nextRole !== UserRole::Admin) {
            return ! User::query()
                ->whereIn('role', [UserRole::Admin->value, 'owner'])
                ->where('id', '!=', $user->id)
                ->exists();
        }

        return false;
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
