<?php

declare(strict_types=1);

use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Profile photo upload.
 *
 * MediaService re-encodes every image, so an upload that arrives claiming to
 * be a PNG but is not one never reaches the disk as a servable file. These
 * tests check the seam between the form and that service.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

function photoUploader(): User
{
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    Member::factory()->approved()->create(['user_id' => $user->id, 'photo_path' => null]);

    return $user;
}

it('stores an uploaded photo and records the path', function (): void {
    $user = photoUploader();

    $this->actingAs($user)
        ->post('/my/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg', 800, 800),
        ])
        ->assertRedirect();

    $member = $user->fresh()->member;

    expect($member->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($member->photo_path);
});

it('rejects a file that is not an image', function (): void {
    $user = photoUploader();

    $this->actingAs($user)
        ->post('/my/photo', [
            'photo' => UploadedFile::fake()->create('resume.pdf', 20, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');

    expect($user->fresh()->member->photo_path)->toBeNull();
});

it('rejects a file over the collection size limit', function (): void {
    $user = photoUploader();

    $limitKb = (int) config('media.collections.profile.max_kb');

    $this->actingAs($user)
        ->post('/my/photo', [
            'photo' => UploadedFile::fake()->create('huge.jpg', $limitKb + 1, 'image/jpeg'),
        ])
        ->assertSessionHasErrors('photo');
});

it('requires a file', function (): void {
    $this->actingAs(photoUploader())
        ->post('/my/photo', [])
        ->assertSessionHasErrors('photo');
});

it('is closed to a signed-in user with no alumni record', function (): void {
    $user = User::factory()->create();
    $user->syncRoles(['Member']);

    // Office staff with a login but no membership have nothing to upload to.
    $this->actingAs($user)
        ->post('/my/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ])
        ->assertForbidden();
});

it('is closed to a guest', function (): void {
    $this->post('/my/photo', [
        'photo' => UploadedFile::fake()->image('me.jpg'),
    ])->assertRedirect('/login');
});

it('offers the upload limit to the form so it cannot promise the wrong size', function (): void {
    $this->actingAs(photoUploader())
        ->get('/my/profile')
        ->assertInertia(fn ($page) => $page
            ->where('options.photo_max_kb', (int) config('media.collections.profile.max_kb'))
        );
});
