<?php

namespace Tests\Feature\Reports;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Al borrar un perfil reportado, el avatar se elimina del disco solo cuando la transacción ya se ha confirmado.
 */
class ResolveReportAfterCommitTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_avatar_de_un_perfil_se_borra_despues_del_commit(): void
    {
        $target = User::factory()->create();
        $path = "avatars/{$target->id}/a.webp";
        $target->profile->update(['avatar' => $path]);
        $report = Report::factory()->create([
            'reporter_id' => User::factory()->create()->id,
            'reportable_type' => User::class,
            'reportable_id' => $target->getKey(),
            'reason' => 'spam',
        ]);
        $this->actingAsAdmin();

        $levels = [];
        $this->mock(MediaStorageInterface::class, function (MockInterface $mock) use ($path, &$levels) {
            $mock->shouldReceive('delete')
                ->once()
                ->with('public', $path)
                ->andReturnUsing(function () use (&$levels) {
                    $levels[] = DB::transactionLevel();
                });
        });
        $base = DB::transactionLevel();

        $this->postJson("/api/admin/reports/{$report->id}/resolve", ['action' => 'delete', 'note' => 'Incumple las normas'])
            ->assertOk();

        $this->assertSame([$base], $levels);
    }
}
