# Changelog

## [1.0.0] - 2025-06-10

### Added
- Initial release
- `ImageCaptionUpload` form component extending Filament `FileUpload`
- Per-image caption input injected via Alpine.js `MutationObserver`
- UUID cache for reliable caption-to-file mapping across reorders and upload race conditions
- `captionPlaceholder()` fluent method
- Hydration / dehydration handling for `[{image, caption}]` DB format
