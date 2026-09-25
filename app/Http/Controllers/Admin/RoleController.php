<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::withCount(['users', 'permissions'])->orderByDesc('is_system')->orderBy('name')->get();

        return view('admin.roles.index', ['roles' => $roles]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.form', [
            'role' => new Role(),
            'permissionsByModule' => Permission::orderBy('module')->orderBy('label')->get()->groupBy('module'),
            'selectedPermissionIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $this->validateRole($request);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        ActivityLog::record('users', 'created', auth()->user()->name.' a créé le rôle '.$role->name, $role);

        return redirect()->route('admin.roles.index')->with('status', 'Rôle créé.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.form', [
            'role' => $role,
            'permissionsByModule' => Permission::orderBy('module')->orderBy('label')->get()->groupBy('module'),
            'selectedPermissionIds' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $this->validateRole($request, $role);

        // Section 16 : un rôle ne peut jamais se retirer à lui-même une permission qu'il utilise
        // pour gérer les rôles, s'il s'agit du propre rôle de l'acteur — sinon l'acteur se
        // couperait l'accès à cette page sans recours.
        if ($request->user()->role_id === $role->id && ! in_array('users.manage_roles', $data['permissions'] ?? [], true) && ! $request->user()->isSuperAdmin()) {
            throw ValidationException::withMessages(['permissions' => 'Vous ne pouvez pas retirer à votre propre rôle la permission de gérer les rôles.']);
        }

        $role->update([
            'name' => $role->slug === Role::SUPER_ADMIN ? $role->name : $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        // Le Super Admin possède déjà toutes les permissions par construction (User::hasPermission)
        // — on ne synchronise jamais sa liste explicite pour éviter toute confusion dans l'UI.
        if ($role->slug !== Role::SUPER_ADMIN) {
            $role->permissions()->sync($data['permissions'] ?? []);
        }

        ActivityLog::record('users', 'updated', auth()->user()->name.' a modifié les permissions du rôle '.$role->name, $role);

        return redirect()->route('admin.roles.index')->with('status', 'Rôle mis à jour.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Ce rôle est attribué à au moins un utilisateur, il ne peut pas être supprimé.']);
        }

        $name = $role->name;
        $role->delete();

        ActivityLog::record('users', 'deleted', auth()->user()->name.' a supprimé le rôle '.$name);

        return redirect()->route('admin.roles.index')->with('status', 'Rôle supprimé.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
    }
}
