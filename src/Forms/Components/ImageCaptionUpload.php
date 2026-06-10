<?php

namespace Imsyuan\ImageCaptionUpload\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Arr;

/**
 * ImageCaptionUpload — extends FileUpload to store [{image, caption}] per file.
 *
 * State format in DB: [['image' => 'path', 'caption' => 'text'], ...]
 * Filepond receives:  ['path1', 'path2', ...]  (parent handles the rest)
 * Captions are synced via a sibling Livewire data key and $wire.entangle in Alpine.
 */
class ImageCaptionUpload extends FileUpload
{
    protected string $view = 'image-caption-upload::forms.components.image-caption-upload';

    protected string|Closure $captionPlaceholder = 'Caption...';

    public function captionPlaceholder(string|Closure $placeholder): static
    {
        $this->captionPlaceholder = $placeholder;

        return $this;
    }

    public function getCaptionPlaceholder(): string
    {
        return $this->evaluate($this->captionPlaceholder);
    }

    /** Key inside $livewire->data used to store the captions map. */
    public function getCaptionsStateKey(): string
    {
        return '_icap_'.str_replace(['.', '[', ']'], '_', $this->getStatePath(false));
    }

    /** Dotted path for $wire.entangle in the Blade view. */
    public function getCaptionsEntanglePath(): string
    {
        return 'data.'.$this->getCaptionsStateKey();
    }

    /** Returns the current captions map (uuid → caption) for Alpine initialization. */
    public function getInitialCaptions(): array
    {
        return $this->getLivewire()->data[$this->getCaptionsStateKey()] ?? [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // afterStateHydrated is a single ?Closure (last-write-wins), not an array.
        // BaseFileUpload::setUp() already registered its closure; capture it so we can
        // chain our pre-processing (detect [{image,caption}] format) in front of it.
        $parentHydrate = $this->afterStateHydrated;

        $this->afterStateHydrated(function (self $component, mixed $state) use ($parentHydrate): void {
            if (! blank($state)) {
                $items = Arr::wrap($state);
                $first = reset($items);

                // Only act on [{image, caption}] format; skip plain path arrays.
                if (is_array($first)) {
                    // Extract {path: caption} temporarily.
                    $pathCaptions = collect($items)
                        ->mapWithKeys(fn (array $item): array => [
                            ($item['image'] ?? '') => ($item['caption'] ?? ''),
                        ])
                        ->filter(fn ($caption, string $path): bool => ! blank($path))
                        ->toArray();

                    // Reduce to plain paths so the parent's callback gets plain strings.
                    $paths = collect($items)
                        ->map(fn (array $item): string => $item['image'] ?? '')
                        ->filter()
                        ->values()
                        ->toArray();

                    // Let parent assign UUIDs — state becomes {uuid: path}.
                    if ($parentHydrate) {
                        $component->evaluate($parentHydrate, ['state' => $paths]);
                    }

                    // Remap captions from {path: caption} to {uuid: caption} so Alpine
                    // can look them up directly via the filepond--item UUID.
                    $uuidCaptions = collect($component->getState())
                        ->mapWithKeys(fn (string $path, string $uuid): array => [
                            $uuid => $pathCaptions[$path] ?? '',
                        ])
                        ->toArray();

                    $component->getLivewire()->data[$component->getCaptionsStateKey()] = $uuidCaptions;

                    return;
                }
            }

            // Not our special format — delegate to parent's callback unchanged.
            if ($parentHydrate) {
                $component->evaluate($parentHydrate, ['state' => $state]);
            }

            // Ensure the captions key always exists so $wire.entangle never fails.
            $key = $component->getCaptionsStateKey();
            if (! array_key_exists($key, $component->getLivewire()->data)) {
                $component->getLivewire()->data[$key] = [];
            }
        });

        // Override dehydrateStateUsing AFTER parent registers its version (last-write-wins).
        // $state here is {uuid: path}; captions are {uuid: caption}.
        $this->dehydrateStateUsing(function (self $component, ?array $state): array {
            $captions = $component->getLivewire()->data[$component->getCaptionsStateKey()] ?? [];

            return collect($state ?? [])
                ->map(fn (string $path, string $uuid): array => [
                    'image' => $path,
                    'caption' => $captions[$uuid] ?? '',
                ])
                ->values()
                ->toArray();
        });
    }
}
