<?php

namespace App\Jobs;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\Proposal;
use App\Services\Media\MediaManager;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessProposalImagesJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	private const MAX_BYTES = 5_242_880;
	private const TEMP_DIR = 'temp_uploads';
	private const TEMP_DISK = 'local';
	private const PUBLIC_DISK = 'public';

	public function __construct(
		public Proposal $proposal,
		public array $rawItems,
	) {}

	public function handle(
		MediaStorageInterface $storage,
		RemoteImageDownloader $downloader,
		MediaManager $mediaManager,
	): void {
		$proposal = $this->proposal->fresh();
		if (!$proposal) return;

		$categorySlug = $proposal->category()->value('slug') ?? 'general';
		$images = [];

		foreach ($this->rawItems as $rawItem) {
			try {
				$variants = $this->processItem($rawItem, $proposal, $categorySlug, $downloader, $mediaManager, $storage);

				if (!empty($variants)) {
					$images[] = [
						'variants' => $variants,
						'disk' => self::PUBLIC_DISK,
						'alt' => null,
						'order' => count($images),
					];
				}
			} catch (\Throwable $exception) {
				Log::warning('No se pudo procesar imagen de propuesta.', [
					'proposal_id' => $proposal->id,
					'error' => $exception->getMessage(),
				]);
			}
		}

		$proposal->update(['images' => $images]);
	}

	private function processItem(
		mixed $rawItem, Proposal $proposal, string $categorySlug,
		RemoteImageDownloader $downloader, MediaManager $mediaManager, MediaStorageInterface $storage
	): array {
		if (!is_string($rawItem) || trim($rawItem) === '') return [];

		$rawItem = trim($rawItem);
		$tempPath = $downloader->isRemoteUrl($rawItem)
			? $downloader->downloadToTemp($rawItem, self::MAX_BYTES, self::TEMP_DISK, self::TEMP_DIR)
			: $rawItem;

		$absoluteTempPath = Storage::disk(self::TEMP_DISK)->path($tempPath);

		$targetDir = 'proposals/' . $categorySlug . '/' . $proposal->id;
		$variants = $mediaManager->processItemVariants($absoluteTempPath, $targetDir);

		$storage->delete(self::TEMP_DISK, $tempPath);

		return $variants;
	}
}