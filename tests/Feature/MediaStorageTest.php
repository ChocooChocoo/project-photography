<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Media delivery depends on one invariant: the directory the "public" disk
 * writes to must be the same directory the web server serves. Uploads go
 * through Storage::disk('public'), reads go through asset('storage/...'), and
 * nothing bridges the two — no symlink, no copy step. These tests fail if that
 * invariant is broken again.
 */
class MediaStorageTest extends TestCase
{
    private string $probe = 'media-storage-test/probe.txt';

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory('media-storage-test');

        parent::tearDown();
    }

    public function test_public_disk_writes_into_the_served_directory(): void
    {
        $this->assertSame(
            public_path('storage'),
            config('filesystems.disks.public.root'),
            'The public disk must write straight into the web-served directory.'
        );
    }

    public function test_no_storage_symlink_is_declared(): void
    {
        $this->assertSame(
            [],
            config('filesystems.links'),
            'Media delivery must not depend on `php artisan storage:link`.'
        );
    }

    public function test_an_uploaded_file_lands_where_asset_urls_point(): void
    {
        Storage::disk('public')->put($this->probe, 'probe');

        // asset('storage/'.$path) resolves to public/storage/$path on disk.
        $this->assertFileExists(public_path('storage/'.$this->probe));

        Storage::disk('public')->delete($this->probe);

        $this->assertFileDoesNotExist(public_path('storage/'.$this->probe));
    }
}
