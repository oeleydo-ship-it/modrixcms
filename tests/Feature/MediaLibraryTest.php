<?php

namespace Tests\Feature;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        Storage::fake('public');

        return tap(User::factory()->admin()->create(), fn (User $admin) => $this->actingAs($admin));
    }

    public function test_guests_and_non_admins_cannot_use_the_media_screen(): void
    {
        $this->get(route('admin.media.index'))->assertRedirect();
        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('a.jpg')]])->assertRedirect();
    }

    public function test_an_admin_can_upload_an_image_and_a_document_together(): void
    {
        $admin = $this->signIn();

        $this->post(route('admin.media.store'), [
            'files' => [
                UploadedFile::fake()->image('Team Photo.jpg', 800, 600),
                UploadedFile::fake()->create('Price list.pdf', 40, 'application/pdf'),
            ],
        ])->assertSessionHas('status', '2 files uploaded.');

        $image = MediaItem::query()->where('kind', 'image')->firstOrFail();
        $this->assertSame(800, $image->width);
        $this->assertSame($admin->id, $image->uploaded_by);
        $this->assertMatchesRegularExpression('#^media/\d{4}/\d{2}/team-photo-[a-z0-9]{6}\.jpg$#', $image->path);
        Storage::disk('public')->assertExists($image->path);

        $document = MediaItem::query()->where('kind', 'file')->firstOrFail();
        $this->assertSame('Price list.pdf', $document->name);
        $this->assertNull($document->width);
    }

    public function test_unsafe_or_mislabelled_files_are_rejected_but_good_ones_still_upload(): void
    {
        $this->signIn();

        $disguised = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($disguised, '<?php echo 1;');

        $response = $this->post(route('admin.media.store'), [
            'files' => [
                UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml'),
                UploadedFile::fake()->create('run.php', 2, 'text/x-php'),
                new UploadedFile($disguised, 'fake.png', null, null, true),
                UploadedFile::fake()->image('ok.png'),
            ],
        ]);

        $response->assertSessionHasErrors('files');
        $this->assertSame(['ok.png'], MediaItem::query()->pluck('name')->all());
        $this->assertCount(1, Storage::disk('public')->allFiles('media'));
    }

    public function test_oversized_files_are_rejected(): void
    {
        $this->signIn();
        config(['cms.media.max_upload_mb' => 1]);

        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->create('big.pdf', 2048, 'application/pdf')]])
            ->assertSessionHasErrors('files');

        $this->assertSame(0, MediaItem::query()->count());
    }

    public function test_the_list_can_be_filtered_by_kind_and_searched(): void
    {
        $this->signIn();
        $this->post(route('admin.media.store'), ['files' => [
            UploadedFile::fake()->image('beach.jpg'),
            UploadedFile::fake()->create('annual-report.pdf', 10, 'application/pdf'),
        ]]);

        $this->getJson(route('admin.media.list', ['kind' => 'file']))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'annual-report.pdf');
        $this->getJson(route('admin.media.list', ['q' => 'beach']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.kind', 'image');
        $this->getJson(route('admin.media.list'))->assertJsonCount(2, 'data');
        $this->get(route('admin.media.index', ['kind' => 'image']))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Media/Index')->where('counts.all', 2)->where('counts.file', 1)->has('items.data', 1));
    }

    public function test_an_item_can_be_renamed_and_deleted_with_its_file(): void
    {
        $this->signIn();
        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')]]);
        [$first, $second] = MediaItem::query()->orderBy('name')->get()->all();

        $this->put(route('admin.media.update', $first), ['name' => 'Hero banner', 'alt' => 'A banner'])->assertSessionHas('status');
        $this->assertSame('A banner', $first->refresh()->alt);

        $this->delete(route('admin.media.destroy', $first))->assertSessionHas('status');
        Storage::disk('public')->assertMissing($first->path);

        $this->delete(route('admin.media.destroy-many'), ['ids' => [$second->id]])->assertSessionHas('status');
        $this->assertSame(0, MediaItem::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('media'));
    }

    public function test_the_builder_picker_only_lists_images_and_videos_by_default(): void
    {
        $this->signIn();
        $this->post(route('admin.media.store'), ['files' => [
            UploadedFile::fake()->image('pic.jpg'),
            UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ]]);

        $this->getJson('/api/v1/media')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/media?kind=file')->assertJsonCount(1, 'data');
    }
}
