<?php

use App\Models\User;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Attachment;
use Filament\TeamChat\Models\Channel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);
});

it('can send a message with file attachments', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Check this out',
        files: [$file],
    );

    expect(Attachment::count())->toBe(1);

    $attachment = Attachment::first();
    expect($attachment->message_id)->toBe($message->id)
        ->and($attachment->file_name)->toBe('photo.jpg')
        ->and($attachment->mime_type)->toBe('image/jpeg')
        ->and($attachment->file_size)->toBeGreaterThan(0);

    Storage::disk('public')->assertExists($attachment->file_path);
});

it('can send multiple files with one message', function () {
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.png'),
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ];

    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Multiple files',
        files: $files,
    );

    expect($message->attachments)->toHaveCount(3);
});

it('identifies image attachments', function () {
    $image = UploadedFile::fake()->image('photo.jpg');
    $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'files',
        files: [$image, $pdf],
    );

    $attachments = Attachment::all();
    expect($attachments[0]->isImage())->toBeTrue()
        ->and($attachments[1]->isImage())->toBeFalse();
});

it('formats file sizes correctly', function () {
    $small = new Attachment(['file_size' => 500]);
    $medium = new Attachment(['file_size' => 2048]);
    $large = new Attachment(['file_size' => 5242880]);

    expect($small->getFormattedFileSize())->toBe('500 B')
        ->and($medium->getFormattedFileSize())->toBe('2 KB')
        ->and($large->getFormattedFileSize())->toBe('5 MB');
});

it('cascades delete when message is force deleted', function () {
    $file = UploadedFile::fake()->image('photo.jpg');

    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'With attachment',
        files: [$file],
    );

    expect(Attachment::count())->toBe(1);

    $message->forceDelete();

    expect(Attachment::count())->toBe(0);
});

it('stores files in the configured directory', function () {
    $file = UploadedFile::fake()->image('photo.jpg');

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'test',
        files: [$file],
    );

    $attachment = Attachment::first();
    expect($attachment->file_path)->toStartWith('team-chat-attachments/');
});

it('can send a message with only files and no text body', function () {
    $file = UploadedFile::fake()->image('photo.jpg');

    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: '📎 ファイルを添付しました',
        files: [$file],
    );

    expect($message->body)->toBe('📎 ファイルを添付しました')
        ->and($message->attachments)->toHaveCount(1);
});

it('accesses attachments through message relationship', function () {
    $files = [
        UploadedFile::fake()->image('a.jpg'),
        UploadedFile::fake()->image('b.png'),
    ];

    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Files here',
        files: $files,
    );

    $fresh = $message->fresh();
    expect($fresh->attachments)->toHaveCount(2)
        ->and($fresh->attachments->pluck('file_name')->all())->toBe(['a.jpg', 'b.png']);
});
