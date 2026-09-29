<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryAccessionSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian = User::factory()->create([
            'role' => 'librarian',
            'status' => 'active',
        ]);
    }

    public function test_librarian_can_search_the_catalogue_by_accession_number(): void
    {
        $matchingCopy = $this->createBookCopy('The River Between', 'ACC-00421', 'BC-90001');
        $this->createBookCopy('Things Fall Apart', 'ACC-00999', 'BC-90002');

        $this->actingAs($this->librarian)
            ->get(route('librarian.books.index', ['search' => $matchingCopy->accession_no]))
            ->assertOk()
            ->assertSee('The River Between')
            ->assertSee('ACC-00421')
            ->assertSee('BC-90001')
            ->assertDontSee('Things Fall Apart');
    }

    public function test_copy_lookup_accepts_either_accession_number_or_barcode(): void
    {
        $copy = $this->createBookCopy('Maru', 'ACC-00107', 'BC-70107');

        $this->actingAs($this->librarian)
            ->getJson(route('librarian.borrowings.lookup-book-copy', ['barcode' => $copy->accession_no]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('copy.id', $copy->id)
            ->assertJsonPath('copy.accession_no', 'ACC-00107');

        $this->actingAs($this->librarian)
            ->getJson(route('librarian.borrowings.lookup-book-copy', ['barcode' => $copy->barcode]))
            ->assertOk()
            ->assertJsonPath('copy.id', $copy->id);
    }

    public function test_librarian_can_issue_and_return_a_copy_by_accession_number(): void
    {
        $copy = $this->createBookCopy('When Rain Clouds Gather', 'ACC-00200', 'BC-70200');
        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id]);

        $this->actingAs($this->librarian)
            ->post(route('librarian.borrowings.issue'), [
                'barcode' => $copy->accession_no,
                'borrower_type' => 'teacher',
                'teacher_id' => $teacher->id,
                'student_id' => null,
                'issued_at' => '2026-08-24',
                'due_at' => '2026-09-07',
            ])
            ->assertRedirect(route('librarian.borrowings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('library_borrowings', [
            'book_copy_id' => $copy->id,
            'teacher_id' => $teacher->id,
            'status' => 'borrowed',
        ]);
        $this->assertSame('borrowed', $copy->fresh()->status);
        $this->assertFalse($copy->fresh()->is_available);

        $this->actingAs($this->librarian)
            ->post(route('librarian.borrowings.return'), [
                'barcode' => $copy->accession_no,
                'returned_at' => '2026-08-25',
            ])
            ->assertRedirect(route('librarian.borrowings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('library_borrowings', [
            'book_copy_id' => $copy->id,
            'status' => 'returned',
            'returned_at' => '2026-08-25 00:00:00',
        ]);
        $this->assertSame('available', $copy->fresh()->status);
        $this->assertTrue($copy->fresh()->is_available);
    }

    private function createBookCopy(string $title, string $accessionNumber, string $barcode): BookCopy
    {
        $book = Book::create([
            'title' => $title,
            'author' => 'Test Author',
            'is_active' => true,
        ]);

        return $book->copies()->create([
            'accession_no' => $accessionNumber,
            'barcode' => $barcode,
            'shelf_location' => 'A-1',
            'status' => 'available',
            'is_available' => true,
        ]);
    }
}
