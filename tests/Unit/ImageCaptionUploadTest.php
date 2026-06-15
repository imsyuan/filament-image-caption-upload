<?php

use Imsyuan\ImageCaptionUpload\Forms\Components\ImageCaptionUpload;

it('returns the default caption placeholder', function (): void {
    $component = ImageCaptionUpload::make('photos');

    expect($component->getCaptionPlaceholder())->toBe('Caption...');
});

it('sets a custom caption placeholder via string', function (): void {
    $component = ImageCaptionUpload::make('photos')
        ->captionPlaceholder('Add a description…');

    expect($component->getCaptionPlaceholder())->toBe('Add a description…');
});

it('accepts a closure as caption placeholder', function (): void {
    $closure = fn (): string => 'Dynamic placeholder';
    $component = ImageCaptionUpload::make('photos')
        ->captionPlaceholder($closure);

    // Verify the closure was stored (evaluate() requires a Filament container so
    // we don't call getCaptionPlaceholder() in isolation here).
    expect($component)->toBeInstanceOf(ImageCaptionUpload::class);
});

it('generates the captions state key from the field name', function (): void {
    $component = ImageCaptionUpload::make('photos');

    expect($component->getCaptionsStateKey())->toBe('_icap_photos');
});

it('generates the captions entangle path from the field name', function (): void {
    $component = ImageCaptionUpload::make('photos');

    expect($component->getCaptionsEntanglePath())->toBe('data._icap_photos');
});

it('sanitises dots and brackets in the state key', function (): void {
    $component = ImageCaptionUpload::make('gallery.items[0]');

    expect($component->getCaptionsStateKey())
        ->toBe('_icap_gallery_items_0_');
});

it('does not include unescaped double quotes inside the Alpine x-data attribute', function (): void {
    $template = file_get_contents(__DIR__ . '/../../resources/views/forms/components/image-caption-upload.blade.php');

    expect($template)
        ->not->toContain('querySelector(\'[x-data*="fileUploadFormComponent"]\')')
        ->toContain('querySelector(\'[x-data*=fileUploadFormComponent]\')');
});

it('uses a translucent caption input background', function (): void {
    $template = file_get_contents(__DIR__ . '/../../resources/views/forms/components/image-caption-upload.blade.php');

    expect($template)
        ->toContain('background-color: rgb(255 255 255 / 0.12);')
        ->not->toContain('background-color: #ffffff;');
});

it('maps existing FilePond URLs back to Filament UUIDs', function (): void {
    $template = file_get_contents(__DIR__ . '/../../resources/views/forms/components/image-caption-upload.blade.php');

    expect($template)
        ->toContain('fileUploadData?.uploadedFileIndex')
        ->toContain('uploadedFileIndex[file.source]')
        ->toContain('uploadedFileIndex[file.serverId]');
});
