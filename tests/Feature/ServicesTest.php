<?php

use App\Livewire\Services\Categories;
use App\Livewire\Services\Index;
use App\Livewire\Services\Prices;
use App\Models\Professional;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('solo el admin entra a servicios', function () {
    $this->actingAs($this->admin)->get(route('services.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('services.categories'))->assertOk();
    $this->actingAs($this->admin)->get(route('services.prices'))->assertOk();

    $this->actingAs(User::factory()->recepcion()->create())->get(route('services.index'))->assertForbidden();
    $this->actingAs(User::factory()->profesional()->create())->get(route('services.prices'))->assertForbidden();
});

test('crea un servicio con sus profesionales', function () {
    $category = ServiceCategory::factory()->create();
    $pros = Professional::factory()->count(2)->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('create', $category->id)
        ->set('name', 'Capping en gel')
        ->set('price', 20000)
        ->set('duration', 75)
        ->set('professionalIds', $pros->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::where('name', 'Capping en gel')->first();

    expect($service->price)->toBe(20000)
        ->and($service->duration_minutes)->toBe(75)
        ->and($service->service_category_id)->toBe($category->id)
        ->and($service->professionals()->count())->toBe(2);
});

test('valida precio y duración', function () {
    $category = ServiceCategory::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('create', $category->id)
        ->set('name', 'Algo')
        ->set('price', -10)
        ->set('duration', 2)
        ->call('save')
        ->assertHasErrors(['price', 'duration']);
});

test('seleccionar todos los profesionales', function () {
    Professional::factory()->count(3)->create();
    Professional::factory()->inactive()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('create')
        ->call('toggleAllProfessionals')
        ->assertCount('professionalIds', 3)
        ->call('toggleAllProfessionals')
        ->assertCount('professionalIds', 0);
});

test('reordena servicios dentro de la categoría', function () {
    $category = ServiceCategory::factory()->create();
    $a = Service::factory()->for($category, 'category')->create(['sort_order' => 1]);
    $b = Service::factory()->for($category, 'category')->create(['sort_order' => 2]);

    Livewire::actingAs($this->admin)->test(Index::class)->call('move', $b->id, 'up');

    expect($b->fresh()->sort_order)->toBe(1)
        ->and($a->fresh()->sort_order)->toBe(2);
});

test('no elimina una categoría con servicios', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->for($category, 'category')->create();

    Livewire::actingAs($this->admin)->test(Categories::class)->call('delete', $category->id);

    expect(ServiceCategory::find($category->id))->not->toBeNull();
});

test('aplica un aumento porcentual con redondeo y guarda', function () {
    $category = ServiceCategory::factory()->create();
    $s1 = Service::factory()->for($category, 'category')->create(['price' => 14000]);
    $s2 = Service::factory()->for($category, 'category')->create(['price' => 9050]);

    Livewire::actingAs($this->admin)
        ->test(Prices::class)
        ->set('percent', 10)
        ->set('rounding', '100')
        ->call('applyIncrease')
        ->assertSet("prices.{$s1->id}", 15400)
        ->assertSet("prices.{$s2->id}", 10000) // 9955 → 10000
        ->call('save')
        ->assertHasNoErrors();

    expect($s1->fresh()->price)->toBe(15400)
        ->and($s2->fresh()->price)->toBe(10000);
});

test('el aumento no se guarda hasta tocar guardar', function () {
    $service = Service::factory()->create(['price' => 10000]);

    Livewire::actingAs($this->admin)
        ->test(Prices::class)
        ->set('percent', 20)
        ->call('applyIncrease');

    expect($service->fresh()->price)->toBe(10000);
});

test('edición manual de precios', function () {
    $service = Service::factory()->create(['price' => 10000]);

    Livewire::actingAs($this->admin)
        ->test(Prices::class)
        ->set("prices.{$service->id}", 12500)
        ->call('save')
        ->assertHasNoErrors();

    expect($service->fresh()->price)->toBe(12500);
});
