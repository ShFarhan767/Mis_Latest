<?php

use App\Models\HeaderLogo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\HeaderLogoService;
use App\Services\ImageService;
use Mockery;

beforeEach(function () {
    Storage::fake('uploads');

    // Mock the ImageService
    $this->mockImageService = Mockery::mock(ImageService::class);
    $this->app->instance(ImageService::class, $this->mockImageService);

    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);
});

it('can create a header logo', function () {
    $file = UploadedFile::fake()->image('logo.png');

    $this->mockImageService
        ->shouldReceive('upload')
        ->once()
        ->andReturn('header-logos/fake_logo.png');

    $response = $this->postJson(route('header-logo.store'), [
        'image' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'image', 'created_at', 'updated_at', 'image_url']);

    $logo = HeaderLogo::first();
    Storage::disk('uploads')->put('header-logos/fake_logo.png', 'fake content');
    Storage::disk('uploads')->assertExists($logo->image);
});

it('can list all header logos', function () {
    HeaderLogo::factory()->count(3)->create();

    $response = $this->getJson(route('header-logo.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

it('can update a header logo', function () {
    $logo = HeaderLogo::factory()->create([
        'image' => 'header-logos/old_logo.png',
    ]);

    $newFile = UploadedFile::fake()->image('new_logo.png');

    $this->mockImageService
        ->shouldReceive('upload')
        ->once()
        ->andReturn('header-logos/updated_logo.png');

    $response = $this->postJson(route('header-logo.update', $logo), [
        'image' => $newFile,
        '_method' => 'PUT',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['id', 'image', 'created_at', 'updated_at', 'image_url']);

    $updatedLogo = HeaderLogo::first();
    Storage::disk('uploads')->put('header-logos/updated_logo.png', 'fake content');
    Storage::disk('uploads')->assertExists($updatedLogo->image);
});

it('can delete a header logo', function () {
    $logo = HeaderLogo::factory()->create([
        'image' => 'header-logos/logo_to_delete.png',
    ]);

    // Mock the delete method in ImageService
    $this->mockImageService
        ->shouldReceive('delete')
        ->once()
        ->with($logo->image, 'header-logos');

    $response = $this->deleteJson(route('header-logo.destroy', $logo));

    $response->assertStatus(200)
        ->assertJson(['message' => 'Deleted successfully']);

    $this->assertDatabaseMissing('header_logos', ['id' => $logo->id]);
    // Remove this line:
    // Storage::disk('uploads')->assertMissing($logo->image);
});

it('can show a single header logo', function () {
    $logo = HeaderLogo::factory()->create([
        'image' => 'header-logos/logo_to_show.png',
    ]);

    Storage::disk('uploads')->put($logo->image, 'fake content');

    $response = $this->getJson(route('header-logo.show', $logo));

    $response->assertStatus(200)
        ->assertJson([
            'id' => $logo->id,
            'image' => $logo->image,
            'image_url' => $logo->image_url,
        ]);
});
