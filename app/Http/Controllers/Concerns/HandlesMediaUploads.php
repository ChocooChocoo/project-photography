<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HandlesMediaUploads
{
    /**
     * Merge retained and newly uploaded files for one media field and delete dropped files.
     * The client lists retained paths in `$keepField[]`; sending `{$keepField}_sent=1` with no
     * paths means "remove all existing". Without either, all existing files are kept.
     *
     * @return array<int, string> relative paths on the public disk
     */
    protected function syncMedia(Request $request, string $field, string $keepField, array $existing, string $dir, int $max): array
    {
        if ($request->has($keepField)) {
            $keep = (array) $request->input($keepField);
        } else {
            $keep = $request->boolean($keepField.'_sent') ? [] : $existing;
        }

        foreach (array_diff($existing, $keep) as $path) {
            Storage::disk('public')->delete($path);
        }

        $files = array_values(array_intersect($existing, $keep));

        foreach ($request->file($field) ?? [] as $upload) {
            if (count($files) >= $max) {
                break;
            }
            $files[] = $upload->store($dir, 'public');
        }

        return $files;
    }

    /** @param  array<int, string>|null  $paths */
    protected function deleteMedia(?array $paths): void
    {
        foreach ($paths ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
