<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPhotoDeliveryTest extends TestCase
{
    public function test_existing_photo_is_served_directly_from_storage_without_a_public_link(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('portrait.jpg');
        $path = $photo->storeAs('students', 'existing-photo.jpg', 'public');

        $response = $this->get('/storage/'.$path);

        $response->assertOk()->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(Storage::disk('public')->get($path), $response->streamedContent());
    }

    public function test_missing_photos_and_non_image_files_are_not_served(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('students/disguised.jpg', '<?php echo "not an image";');
        Storage::disk('public')->put('students/script.php', '<?php echo "not an image";');
        Storage::disk('public')->put('private.txt', 'not public student content');

        foreach ([
            '/storage/students/missing.jpg',
            '/storage/students/disguised.jpg',
            '/storage/students/script.php',
            '/storage/students/../private.txt',
            '/storage/students/nested/photo.jpg',
        ] as $url) {
            $this->assertContains($this->get($url)->status(), [403, 404], $url.' must not be served.');
        }
    }
}
