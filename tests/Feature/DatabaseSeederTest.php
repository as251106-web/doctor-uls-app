<?php

use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds three doctor accounts with one doctor per service and can be rerun', function () {
    $this->seed(DatabaseSeeder::class);

    $doctorRole = Role::where('nombre', 'doctor')->firstOrFail();
    $doctors = User::where('role_id', $doctorRole->id)->get();
    $services = Service::all();

    expect($doctors)->toHaveCount(3)
        ->and($services)->toHaveCount(3);

    foreach ($services as $service) {
        expect($service->doctors)->toHaveCount(1);
    }

    $this->seed(DatabaseSeeder::class);

    expect(User::where('role_id', $doctorRole->id)->count())->toBe(3)
        ->and(Service::count())->toBe(3);
});
