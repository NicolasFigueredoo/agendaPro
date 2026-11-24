<?php

namespace App\Livewire\Admin\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Usuarios')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'recepcion';

    public bool $active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->resetForm();

        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->role->value;
        $this->active = $user->active;

        $this->showModal = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(Role::class)],
            'active' => ['boolean'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'email',
            'phone' => 'teléfono',
            'role' => 'rol',
            'password' => 'contraseña',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId === auth()->id() && (! $data['active'] || $data['role'] !== Role::Admin->value)) {
            $this->addError('role', 'No podés quitarte el rol de administrador ni desactivar tu propio usuario.');

            return;
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'role' => $data['role'],
            'active' => $data['active'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password']; // el cast "hashed" lo encripta
        }

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update($payload);
            $message = 'Usuario actualizado.';
        } else {
            User::create($payload + ['email_verified_at' => now()]);
            $message = 'Usuario creado.';
        }

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('toast', message: 'No podés desactivar tu propio usuario.', type: 'error');

            return;
        }

        $user = User::findOrFail($id);
        $user->update(['active' => ! $user->active]);

        $this->dispatch('toast', message: $user->active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('toast', message: 'No podés eliminar tu propio usuario.', type: 'error');

            return;
        }

        User::findOrFail($id)->delete();

        $this->dispatch('toast', message: 'Usuario eliminado.');
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'role', 'active', 'password', 'password_confirmation']);
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderByDesc('active')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.users.index', [
            'users' => $users,
            'roles' => Role::options(),
        ]);
    }
}
