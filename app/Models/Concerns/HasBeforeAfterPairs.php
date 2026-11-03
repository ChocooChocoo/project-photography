<?php

namespace App\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Pairs edited gallery photos with their unedited originals (raw_images:
 * edited path => original path) for the client's before-and-after slider.
 */
trait HasBeforeAfterPairs
{
    public static function bootHasBeforeAfterPairs(): void
    {
        // When an edited photo leaves the gallery, its original goes too.
        static::saving(function ($gallery) {
            $pairs = $gallery->raw_images ?? [];
            if (! $pairs || ! $gallery->isDirty('images')) {
                return;
            }

            $kept = array_intersect_key($pairs, array_flip($gallery->images ?? []));
            Storage::disk('public')->delete(array_values(array_diff_key($pairs, $kept)));
            $gallery->raw_images = $kept ?: null;
        });
    }

    /**
     * Store $file as the original of the edited photo at $editedPath,
     * replacing any earlier original. Returns the stored path.
     */
    public function attachOriginal(UploadedFile $file, string $editedPath): string
    {
        $pairs = $this->raw_images ?? [];
        $previous = $pairs[$editedPath] ?? null;

        $path = $file->store(dirname($editedPath).'/originals', 'public');
        $pairs[$editedPath] = $path;
        $this->raw_images = $pairs;
        $this->save();

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }

    /**
     * Original paths in the same order as images (null where none).
     *
     * @return array<int, string|null>
     */
    public function originalsInImageOrder(): array
    {
        $pairs = $this->raw_images ?? [];

        return array_map(fn ($image) => $pairs[$image] ?? null, $this->images ?? []);
    }
}
