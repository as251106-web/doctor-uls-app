<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        return view('doctors.index', [
            'doctors' => $this->doctorAccounts(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('doctors.form', [
            'services' => $this->availableServices(),
            'editingDoctor' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'carnet' => ['required', 'string', 'max:20', 'unique:users,carnet'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated): void {
            $service = Service::query()
                ->whereKey($validated['service_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureServiceIsAssignable($service);

            $doctorRole = Role::query()->where('nombre', 'doctor')->firstOrFail();
            $doctor = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'carnet' => $validated['carnet'],
                'password' => Hash::make($validated['password']),
                'role_id' => $doctorRole->id,
            ]);

            $doctor->services()->sync([$service->id]);
        });

        return redirect()
            ->route('doctors.index')
            ->with('success', 'La cuenta del doctor fue creada y asignada a su especialización.');
    }

    public function edit(User $doctor): View
    {
        $this->authorizeAdmin();
        abort_unless($doctor->isDoctor(), 404);

        $doctor->load('services');

        return view('doctors.form', [
            'services' => $this->availableServices($doctor),
            'editingDoctor' => $doctor,
        ]);
    }

    public function update(Request $request, User $doctor): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless($doctor->isDoctor(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor)],
            'carnet' => ['required', 'string', 'max:20', Rule::unique('users', 'carnet')->ignore($doctor)],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($doctor, $validated): void {
            $lockedDoctor = User::query()
                ->whereKey($doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedDoctor->isDoctor(), 404);

            $service = Service::query()
                ->whereKey($validated['service_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureServiceIsAssignable($service, $lockedDoctor);

            $lockedDoctor->name = $validated['name'];
            $lockedDoctor->email = $validated['email'];
            $lockedDoctor->carnet = $validated['carnet'];

            if (! empty($validated['password'])) {
                $lockedDoctor->password = Hash::make($validated['password']);
            }

            $lockedDoctor->save();
            $lockedDoctor->services()->sync([$service->id]);
        });

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Los datos y la especialización del doctor fueron actualizados.');
    }

    private function doctorAccounts(): Collection
    {
        $doctorRole = Role::query()->where('nombre', 'doctor')->firstOrFail();

        return $doctorRole->users()
            ->with('services')
            ->orderBy('name')
            ->get();
    }

    private function availableServices(?User $editingDoctor = null): Collection
    {
        return Service::query()
            ->where(function (Builder $query) use ($editingDoctor): void {
                $query->where(function (Builder $availableServices): void {
                    $availableServices
                        ->where('activo', true)
                        ->whereDoesntHave('doctors');
                });

                if ($editingDoctor !== null) {
                    $query->orWhereHas(
                        'doctors',
                        fn (Builder $doctors) => $doctors->where('users.id', $editingDoctor->id),
                    );
                }
            })
            ->orderBy('nombre')
            ->get();
    }

    private function ensureServiceIsAssignable(Service $service, ?User $doctor = null): void
    {
        $isCurrentSpecialization = $doctor?->services()
            ->whereKey($service->id)
            ->exists() ?? false;

        if (! $service->activo && ! $isCurrentSpecialization) {
            throw ValidationException::withMessages([
                'service_id' => 'Solo puedes asignar especializaciones activas.',
            ]);
        }

        $assignedDoctors = $service->doctors();

        if ($doctor !== null) {
            $assignedDoctors->where('users.id', '<>', $doctor->id);
        }

        if ($assignedDoctors->exists()) {
            throw ValidationException::withMessages([
                'service_id' => 'Esta especialización ya está asignada a otro doctor.',
            ]);
        }
    }

    private function authorizeAdmin(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->isAdmin(), 403, 'Acceso no autorizado.');
    }
}
