# Filament Image Caption Upload

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imsyuan/filament-image-caption-upload.svg?style=flat-square)](https://packagist.org/packages/imsyuan/filament-image-caption-upload)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/imsyuan/filament-image-caption-upload/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/imsyuan/filament-image-caption-upload/actions?query=workflow%3Atests+branch%3Amain)
[![License](https://img.shields.io/packagist/l/imsyuan/filament-image-caption-upload.svg?style=flat-square)](https://packagist.org/packages/imsyuan/filament-image-caption-upload)

A [Filament v3](https://filamentphp.com) form component that extends `FileUpload` to attach a per-image caption to each uploaded file.

## Database Format

Each field stores an array of `{image, caption}` pairs:

```json
[
  { "image": "photos/abc.jpg", "caption": "Living room view" },
  { "image": "photos/def.jpg", "caption": "Kitchen detail" }
]
```

## Requirements

- PHP 8.1+
- Laravel 10+
- Filament 3.x

## Installation

```bash
composer require imsyuan/filament-image-caption-upload
```

## Usage

```php
use Imsyuan\ImageCaptionUpload\Forms\Components\ImageCaptionUpload;

ImageCaptionUpload::make('photos')
    ->multiple()
    ->image()
    ->captionPlaceholder('Enter a caption…')
    ->reorderable()
    ->appendFiles(),
```

Cast the model attribute as an array:

```php
// app/Models/Post.php
protected $casts = [
    'photos' => 'array',
];
```

## Options

### `captionPlaceholder(string|Closure $placeholder)`

Sets the placeholder text shown inside each caption input. Defaults to `'Caption...'`.

### All `FileUpload` options

`ImageCaptionUpload` extends Filament's built-in `FileUpload`, so all existing options work as-is:
`->image()`, `->multiple()`, `->maxFiles()`, `->reorderable()`, `->panelLayout()`, `->disk()`, `->directory()`, etc.

## How it works

1. **Hydration** — `[{image, caption}]` is split: paths go to Filament's normal UUID-keyed state; captions are stored in a sibling Livewire key (`_icap_<fieldname>`).
2. **Alpine** — A `MutationObserver` watches for FilePond item additions and injects caption `<input>` elements beneath each file panel. Captions are entangled back to Livewire state.
3. **UUID cache** — FilePond internal IDs are mapped to Filament UUIDs so captions survive reorders and upload race conditions.
4. **Dehydration** — `{uuid: path}` and `{uuid: caption}` are zipped back into `[{image, caption}]` before saving.

## License

MIT — see [LICENSE](LICENSE).
