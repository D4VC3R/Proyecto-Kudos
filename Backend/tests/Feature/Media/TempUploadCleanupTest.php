<?php

namespace Tests\Feature\Media;

use App\Jobs\ProcessProposalImagesJob;
use App\Jobs\ProcessUserAvatarJob;
use App\Models\Proposal;
use App\Models\User;
use App\Services\Media\MediaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\FakesRemoteImages;
use Tests\TestCase;

/**
 * Los jobs de avatar y de imágenes de propuesta borran el archivo temporal también cuando el procesado falla.
 */
class TempUploadCleanupTest extends TestCase
{
    use CreatesItems, FakesRemoteImages, RefreshDatabase;

    private const TEMP_PATH = 'temp_uploads/imagen.png';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function putTempImage(): void
    {
        Storage::disk('local')->put(self::TEMP_PATH, $this->fakePng());
    }

    private function proposalFor(User $user): Proposal
    {
        return Proposal::factory()->create([
            'creator_id' => $user->id,
            'category_id' => $this->createCategory()->id,
        ]);
    }

    public function test_el_avatar_borra_el_temporal_al_procesarlo(): void
    {
        $user = User::factory()->create();
        $this->putTempImage();

        app()->call([new ProcessUserAvatarJob($user, self::TEMP_PATH), 'handle']);

        Storage::disk('local')->assertMissing(self::TEMP_PATH);
        $this->assertNotNull($user->fresh('profile')->profile->avatar);
    }

    public function test_el_avatar_borra_el_temporal_aunque_falle_el_procesado(): void
    {
        $user = User::factory()->create();
        $this->putTempImage();
        $this->mock(MediaManager::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('processAvatar')->once()->andThrow(new RuntimeException('Imagen corrupta')));

        try {
            app()->call([new ProcessUserAvatarJob($user, self::TEMP_PATH), 'handle']);
            $this->fail('El job debía propagar el fallo del procesado.');
        } catch (RuntimeException $e) {
            $this->assertSame('Imagen corrupta', $e->getMessage());
        }

        Storage::disk('local')->assertMissing(self::TEMP_PATH);
    }

    public function test_la_propuesta_borra_el_temporal_al_procesarlo(): void
    {
        $proposal = $this->proposalFor(User::factory()->create());
        $this->putTempImage();

        app()->call([new ProcessProposalImagesJob($proposal, [self::TEMP_PATH]), 'handle']);

        Storage::disk('local')->assertMissing(self::TEMP_PATH);
        $images = $proposal->fresh()->images;
        $this->assertCount(1, $images);
        $this->assertArrayHasKey('thumb', $images[0]['variants']);
        $this->assertArrayHasKey('banner', $images[0]['variants']);
    }

    public function test_la_propuesta_borra_el_temporal_aunque_falle_el_procesado(): void
    {
        $proposal = $this->proposalFor(User::factory()->create());
        $this->putTempImage();
        $this->mock(MediaManager::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('processItemVariants')->once()->andThrow(new RuntimeException('Imagen corrupta')));
        Log::spy();

        app()->call([new ProcessProposalImagesJob($proposal, [self::TEMP_PATH]), 'handle']);

        Log::shouldHaveReceived('warning')->once();
        $this->assertSame([], $proposal->fresh()->images);
        Storage::disk('local')->assertMissing(self::TEMP_PATH);
    }
}
