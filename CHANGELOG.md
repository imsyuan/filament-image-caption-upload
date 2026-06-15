# Changelog

## [3.0.1] - 2026-06-15

### Fixed
- Map existing FilePond file URLs back to Filament UUIDs when restoring captions
- Avoid unescaped double quotes in the Alpine `x-data` selector

### Changed
- Use a translucent caption input style for better image-overlay contrast
- Refresh the project cover artwork

## [1.0.0] - 2025-06-10

### Added
- Initial release
- `ImageCaptionUpload` form component extending Filament `FileUpload`
- Per-image caption input injected via Alpine.js `MutationObserver`
- UUID cache for reliable caption-to-file mapping across reorders and upload race conditions
- `captionPlaceholder()` fluent method
- Hydration / dehydration handling for `[{image, caption}]` DB format
