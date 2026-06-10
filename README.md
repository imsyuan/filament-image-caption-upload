# Filament Image Caption Upload

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imsyuan/filament-image-caption-upload.svg?style=flat-square)](https://packagist.org/packages/imsyuan/filament-image-caption-upload)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/imsyuan/filament-image-caption-upload/tests.yml?branch=4.x&label=tests&style=flat-square)](https://github.com/imsyuan/filament-image-caption-upload/actions?query=workflow%3Atests+branch%3A4.x)
[![License](https://img.shields.io/packagist/l/imsyuan/filament-image-caption-upload.svg?style=flat-square)](https://packagist.org/packages/imsyuan/filament-image-caption-upload)

A [Filament v4](https://filamentphp.com) form component that extends the built-in `FileUpload` to attach a per-image caption beneath each uploaded file — with no build step required.

![Demo](https://raw.githubusercontent.com/imsyuan/filament-image-caption-upload/main/art/demo.png)

---

## Features

- **Drop-in replacement** — extends Filament's native `FileUpload`, so every existing option (`->image()`, `->multiple()`, `->reorderable()`, `->disk()`, `->directory()`, `->maxFiles()`, …) works unchanged
- **Zero build step** — no npm, no Vite, no esbuild; pure Blade + Alpine.js inline
- **Reorder-safe** — captions follow their image through drag-and-drop reordering
- **Duplicate-filename safe** — maps FilePond internal IDs to Filament UUIDs directly, so ten files all named `banner.jpg` each keep their own caption
- **Race-condition safe** — captions typed before an upload finishes are preserved and migrated to the final UUID automatically
- **Simple storage** — saved as a plain `[{image, caption}]` JSON array in a single column; no extra tables or relationships needed

---

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.x

---

## Installation

Choose the version that matches your Filament installation:

| Filament | Version |
|---|---|
| 3.x | `^1.0` |
| 4.x | `^2.0` |
| 5.x | `^3.0` |

```bash
# Filament 3.x
composer require imsyuan/filament-image-caption-upload "^1.0"

# Filament 4.x
composer require imsyuan/filament-image-caption-upload "^2.0"

# Filament 5.x
composer require imsyuan/filament-image-caption-upload "^3.0"
```

Laravel auto-discovers the service provider — no manual registration needed.

---

## Usage

```php
use Imsyuan\ImageCaptionUpload\Forms\Components\ImageCaptionUpload;

ImageCaptionUpload::make('photos')
    ->multiple()
    ->image()
    ->reorderable()
    ->captionPlaceholder('Enter a caption…'),
```

Cast the column as an array in your model:

```php
// app/Models/Post.php
protected $casts = [
    'photos' => 'array',
];
```

Each field saves an array of `{image, caption}` pairs:

```json
[
  { "image": "photos/living-room.jpg", "caption": "Living room view" },
  { "image": "photos/kitchen.jpg",     "caption": "Kitchen detail" }
]
```

---

## Options

### `captionPlaceholder(string|Closure $placeholder)`

Sets the placeholder text shown inside each caption input. Accepts a plain string or a closure. Defaults to `'Caption...'`.

```php
// Static
->captionPlaceholder('Add a description…')

// Dynamic
->captionPlaceholder(fn () => __('fields.caption_placeholder'))
```

### All native `FileUpload` options

Because `ImageCaptionUpload` extends Filament's `FileUpload` directly, every built-in option is available:

```php
ImageCaptionUpload::make('photos')
    ->multiple()
    ->image()
    ->maxFiles(10)
    ->reorderable()
    ->appendFiles()
    ->disk('s3')
    ->directory('photos')
    ->visibility('public')
    ->panelLayout('grid')
    ->imageResizeMode('cover')
    ->imageResizeTargetWidth('400')
    ->imageResizeTargetHeight('400'),
```

---

## How it works

1. **Hydration** — `[{image, caption}]` from the database is split on load: file paths go into Filament's UUID-keyed state; captions are stored in a sibling Livewire key (`_icap_<fieldname>`).
2. **Alpine** — A `MutationObserver` watches for FilePond item additions and injects a caption `<input>` beneath each file panel. Typing syncs directly to Livewire state via `$wire.set()`.
3. **UUID mapping** — FilePond's internal IDs are mapped to Filament UUIDs by reading the FilePond instance directly (`pond.getFiles()`), ensuring correctness regardless of stored filename or UUID-based disk naming.
4. **Dehydration** — On save, `{uuid: path}` and `{uuid: caption}` are zipped back into `[{image, caption}]`.

---

## License

MIT — see [LICENSE](LICENSE).
