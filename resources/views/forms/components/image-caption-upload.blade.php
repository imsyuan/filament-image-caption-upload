@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Facades\FilamentView;

    $id                                      = $getId();
    $automaticallyCropImagesAspectRatio      = $getAutomaticallyCropImagesAspectRatio();
    $automaticallyResizeImagesHeight         = $getAutomaticallyResizeImagesHeight();
    $automaticallyResizeImagesWidth          = $getAutomaticallyResizeImagesWidth();
    $isAvatar                                = $isAvatar();
    $livewireKey                             = $getLivewireKey();
    $statePath                               = $getStatePath();
    $isDisabled                              = $isDisabled();
    $hasImageEditor                          = $hasImageEditor();
    $hasCircleCropper                        = $hasCircleCropper();

    $alignment = $getAlignment() ?? Alignment::Start;
    if (! $alignment instanceof Alignment) {
        $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
    }

    $captionPlaceholder    = $getCaptionPlaceholder();
    $captionsEntanglePath  = $getCaptionsEntanglePath();
    $furnitureItemsPath    = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    @once
    <style>
        .ic-caption-input {
            display: block;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-family: inherit;
            color: #ffffff;
            padding: 0.375rem 0.75rem;
            border: 1px solid rgb(255 255 255 / 0.25);
            border-radius: 0.5rem;
            background-color: rgb(255 255 255 / 0.12);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            transition: color 75ms ease, background-color 75ms ease,
                        border-color 75ms ease, box-shadow 75ms ease;
            outline: 2px solid transparent;
            outline-offset: 2px;
        }
        .ic-caption-input::placeholder { color: #d1d5db; }
        .ic-caption-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6 inset;
            position: relative;
            z-index: 1;
        }
        .dark .ic-caption-input {
            background-color: rgb(255 255 255 / 0.12);
            color: #ffffff;
            border-color: rgb(255 255 255 / 0.25);
        }
        .dark .ic-caption-input::placeholder { color: #d1d5db; }
        .dark .ic-caption-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6 inset;
        }
        .ic-caption-wrap {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 0.25rem 0.5rem 0.375rem;
            pointer-events: none;
        }
        .ic-caption-wrap .ic-caption-input { pointer-events: auto; }
    </style>
    @endonce
    {{-- ─────────────────────────────────────────────────────────────────────────
         Outer wrapper: manages captions via Alpine.js + $wire.entangle.
         MutationObserver watches for Filepond panel items; injects caption inputs.
    ──────────────────────────────────────────────────────────────────────────── --}}
    <div
        x-data="{
            _captions: {},
            _placeholder: @js($captionPlaceholder),
            _captionsPath: @js($captionsEntanglePath),
            _fpItemsPath: @js($furnitureItemsPath),
            _fpUuidCache: {},
            _observer: null,

            init() {
                // Pull captions from Livewire state (set by afterStateHydrated on the server).
                // Captions are NOT embedded in the x-data string so it stays static across
                // Livewire re-renders — a dynamic x-data causes Alpine to re-init this component,
                // which breaks Filepond (reverts to plain input).
                this._captions = $wire.get(this._captionsPath) ?? {};

                // MutationObserver: inject caption inputs whenever Filepond adds items.
                this._observer = new MutationObserver(() => this._injectAll());
                this._observer.observe(this.$el, { childList: true, subtree: true });

                // Initial pass (existing files already in DOM on page load).
                this.$nextTick(() => this._injectAll());

                // Re-inject after a new upload finishes.
                // event.detail.file.id = fp_id, event.detail.file.serverId = Filament UUID.
                // Reading from event directly avoids the $wire.get() stale-state race.
                this.$el.addEventListener('FilePond:processfile', (event) => {
                    const detail = event.detail ?? {};
                    const fp_id = detail.file?.id;
                    const realUuid = detail.file?.serverId;
                    if (fp_id && realUuid && !(fp_id in this._fpUuidCache)) {
                        this._fpUuidCache[fp_id] = realUuid;
                        if (this._migrateCaption(fp_id, realUuid)) {
                            $wire.set(this._captionsPath, this._captions, false);
                        }
                    }
                    this.$nextTick(() => this._injectAll());
                });

                // Watch Livewire state for furniture_items changes.
                // $wire.get() may return stale data when FilePond:processfile fires because
                // Livewire's snapshot update and the Filepond success callback can race.
                // Watching the property guarantees _updateCache() runs after state is committed.
                $wire.$watch(@js($furnitureItemsPath), () => {
                    this.$nextTick(() => this._injectAll());
                });
            },

            // Sticky cache: build {filepond_id → filament_uuid} once per item; never overwrite.
            // For multiple existing items: maps FilePond URLs back to Filament UUIDs
            // through the file upload component's uploadedFileIndex.
            // For single new uploads (one uncached item) positional pairing is still safe.
            _updateCache() {
                const state = $wire.get(this._fpItemsPath) ?? {};
                const allUuids = Object.keys(state);
                const cachedUuids = new Set(Object.values(this._fpUuidCache));
                const uncachedUuids = allUuids.filter(u => !cachedUuids.has(u));
                if (uncachedUuids.length === 0) return;

                const domItems = Array.from(this.$el.querySelectorAll('.filepond--item'));
                const uncachedItems = domItems.filter(item => {
                    const fp_id = (item.id ?? '').replace('filepond--item-', '');
                    return fp_id && !(fp_id in this._fpUuidCache);
                });

                if (uncachedItems.length === 0) return;

                // Single uncached item = a new upload completing. Positional pairing is
                // unambiguous here (one item, one uuid) and filename may be a local name.
                if (uncachedItems.length === 1) {
                    const fp_id = (uncachedItems[0].id ?? '').replace('filepond--item-', '');
                    if (fp_id && uncachedUuids.length > 0) {
                        const realUuid = uncachedUuids[0];
                        this._fpUuidCache[fp_id] = realUuid;
                        // If the user typed a caption before upload completed, it was keyed under
                        // fp_id (the fallback). Migrate it to the real uuid so it isn't lost when
                        // _inject() re-renders the item with the correct key.
                        if (this._migrateCaption(fp_id, realUuid)) {
                            $wire.set(this._captionsPath, this._captions, false);
                        }
                    }
                    return;
                }

                // Multiple uncached items (initial page load / component reinit):
                // Existing FilePond items use their URL as source/serverId. Filament keeps
                // the corresponding UUID in uploadedFileIndex, so map through that index.
                const innerEl = this.$el.querySelector('[x-data*=fileUploadFormComponent]');
                const fileUploadData = (innerEl && window.Alpine) ? window.Alpine.$data(innerEl) : null;
                const pond = fileUploadData?.pond;
                const uploadedFileIndex = fileUploadData?.uploadedFileIndex ?? {};

                let migrated = false;

                if (pond) {
                    pond.getFiles().forEach(file => {
                        const realUuid = file.source instanceof File
                            ? file.serverId
                            : (uploadedFileIndex[file.source] ?? uploadedFileIndex[file.serverId] ?? null);

                        if (file.id && realUuid && !(file.id in this._fpUuidCache)) {
                            this._fpUuidCache[file.id] = realUuid;
                            if (this._migrateCaption(file.id, realUuid)) migrated = true;
                        }
                    });
                } else {
                    // Fallback: basename matching (pond inaccessible — e.g., Alpine not yet init).
                    const filenameToUuid = {};
                    for (const uuid of uncachedUuids) {
                        const path = state[uuid];
                        if (typeof path === 'string') {
                            const basename = path.split('/').pop();
                            if (basename) filenameToUuid[basename] = uuid;
                        }
                    }
                    uncachedItems.forEach(item => {
                        const fp_id = (item.id ?? '').replace('filepond--item-', '');
                        if (!fp_id) return;
                        const labelText = item.querySelector('.filepond--file-info-main')?.textContent?.trim();
                        if (labelText && labelText in filenameToUuid) {
                            const realUuid = filenameToUuid[labelText];
                            this._fpUuidCache[fp_id] = realUuid;
                            if (this._migrateCaption(fp_id, realUuid)) migrated = true;
                        }
                    });
                }

                if (migrated) {
                    $wire.set(this._captionsPath, this._captions, false);
                }
            },

            // Moves a caption stored under oldKey to newKey. Returns true if a migration happened.
            _migrateCaption(oldKey, newKey) {
                if (oldKey === newKey) return false;
                if (!(oldKey in (this._captions ?? {}))) return false;
                if (newKey in (this._captions ?? {})) return false;
                this._captions = Object.assign({}, this._captions, { [newKey]: this._captions[oldKey] });
                delete this._captions[oldKey];
                return true;
            },

            _injectAll() {
                this._updateCache();
                this.$el.querySelectorAll('.filepond--item').forEach(item => this._inject(item));
            },

            _inject(item) {
                const fp_id = (item.id ?? '').replace('filepond--item-', '');
                if (!fp_id) return;

                // Use cached Filament UUID; fall back to fp_id for items not yet resolved.
                const uuid = this._fpUuidCache[fp_id] ?? fp_id;

                // Already injected for this uuid — skip.
                if (item.getAttribute('data-ic-key') === uuid) return;

                // Before removing the old wrap, rescue any caption the user already typed.
                // This covers the race where the user types BEFORE the upload completes:
                // the input was injected with fp_id as the temp key, and now we're
                // re-injecting with the real uuid. Without this, the typed value is lost.
                const oldKey = item.getAttribute('data-ic-key');
                const oldInput = item.querySelector('.ic-caption-input');
                if (oldInput && oldKey && oldKey !== uuid) {
                    const typed = oldInput.value;
                    if (typed && !(uuid in (this._captions ?? {}))) {
                        this._captions = Object.assign({}, this._captions ?? {}, { [uuid]: typed });
                        if (oldKey in (this._captions ?? {})) { delete this._captions[oldKey]; }
                        $wire.set(this._captionsPath, this._captions, false);
                    }
                }

                // Remove stale wrapper if uuid changed (e.g. after re-upload).
                item.querySelector('.ic-caption-wrap')?.remove();
                item.setAttribute('data-ic-key', uuid);

                const self = this;

                const wrap = document.createElement('div');
                wrap.className = 'ic-caption-wrap';

                const input = document.createElement('input');
                input.type        = 'text';
                input.className   = 'ic-caption-input';
                input.placeholder = this._placeholder;
                input.value       = (this._captions ?? {})[uuid] ?? '';

                input.addEventListener('input', (e) => {
                    self._captions = Object.assign({}, self._captions ?? {}, { [uuid]: e.target.value });
                    $wire.set(self._captionsPath, self._captions, false);
                });

                // Prevent Filepond from consuming these events (would open the file).
                ['click', 'mousedown', 'focus', 'pointerdown'].forEach(evt =>
                    input.addEventListener(evt, (e) => e.stopPropagation())
                );

                wrap.appendChild(input);
                item.appendChild(wrap);
            },
        }"
    >

        {{-- ───────────────────────────────────────────────────────────────────
             Standard FileUpload Filepond component (identical to original view).
        ──────────────────────────────────────────────────────────────────────── --}}
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('file-upload', 'filament/forms') }}"
            x-data="fileUploadFormComponent({
                        acceptedFileTypes: @js($getAcceptedFileTypes()),
                        imageEditorEmptyFillColor: @js($getImageEditorEmptyFillColor()),
                        imageEditorMode: @js($getImageEditorMode()),
                        imageEditorViewportHeight: @js($getImageEditorViewportHeight()),
                        imageEditorViewportWidth: @js($getImageEditorViewportWidth()),
                        deleteUploadedFileUsing: async (fileKey) => {
                            return await $wire.callSchemaComponentMethod(@js($getKey()), 'deleteUploadedFile', { fileKey })
                        },
                        getUploadedFilesUsing: async () => {
                            return await $wire.callSchemaComponentMethod(@js($getKey()), 'getUploadedFiles')
                        },
                        hasImageEditor: @js($hasImageEditor),
                        hasCircleCropper: @js($hasCircleCropper),
                        canEditSvgs: @js($canEditSvgs()),
                        isSvgEditingConfirmed: @js($isSvgEditingConfirmed()),
                        confirmSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.confirmation')),
                        disabledSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.disabled')),
                        automaticallyCropImagesAspectRatio: @js($automaticallyCropImagesAspectRatio),
                        imagePreviewHeight: @js($getImagePreviewHeight()),
                        automaticallyResizeImagesMode: @js($getAutomaticallyResizeImagesMode()),
                        automaticallyResizeImagesHeight: @js($automaticallyResizeImagesHeight),
                        automaticallyResizeImagesWidth: @js($automaticallyResizeImagesWidth),
                        shouldAutomaticallyUpscaleImagesWhenResizing: @js($shouldAutomaticallyUpscaleImagesWhenResizing()),
                        shouldTransformImage: @js($automaticallyCropImagesAspectRatio || $automaticallyResizeImagesHeight || $automaticallyResizeImagesWidth),
                        automaticallyOpenImageEditorForAspectRatio: @js($getAutomaticallyOpenImageEditorForAspectRatio()),
                        maxFilesValidationMessage: @js(__('filament-forms::components.file_upload.messages.max_files', ['max' => $getMaxFiles()])),
                        isAvatar: @js($isAvatar),
                        isDeletable: @js($isDeletable()),
                        isDisabled: @js($isDisabled),
                        isDownloadable: @js($isDownloadable()),
                        isMultiple: @js($isMultiple()),
                        isOpenable: @js($isOpenable()),
                        isPasteable: @js($isPasteable()),
                        isPreviewable: @js($isPreviewable()),
                        isReorderable: @js($isReorderable()),
                        itemPanelAspectRatio: @js($getItemPanelAspectRatio()),
                        loadingIndicatorPosition: @js($getLoadingIndicatorPosition()),
                        locale: @js(app()->getLocale()),
                        panelAspectRatio: @js($getPanelAspectRatio()),
                        panelLayout: @js($getPanelLayout()),
                        placeholder: @js($getPlaceholder()),
                        maxFiles: @js($getMaxFiles()),
                        maxSize: @js(($size = $getMaxSize()) ? "{$size}KB" : null),
                        minSize: @js(($size = $getMinSize()) ? "{$size}KB" : null),
                        mimeTypeMap: @js($getMimeTypeMap()),
                        maxParallelUploads: @js($getMaxParallelUploads()),
                        removeUploadedFileUsing: async (fileKey) => {
                            return await $wire.callSchemaComponentMethod(@js($getKey()), 'removeUploadedFile', { fileKey })
                        },
                        removeUploadedFileButtonPosition: @js($getRemoveUploadedFileButtonPosition()),
                        reorderUploadedFilesUsing: async (fileKeys) => {
                            return await $wire.callSchemaComponentMethod(@js($getKey()), 'reorderUploadedFiles', { fileKeys })
                        },
                        shouldAppendFiles: @js($shouldAppendFiles()),
                        shouldOrientImageFromExif: @js($shouldOrientImagesFromExif()),
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                        uploadButtonPosition: @js($getUploadButtonPosition()),
                        uploadingMessage: @js($getUploadingMessage()),
                        uploadProgressIndicatorPosition: @js($getUploadProgressIndicatorPosition()),
                        cancelUploadUsing: async (fileKey) => {
                            return await $wire.cancelFormUpload(@js($statePath), fileKey)
                        },
                        uploadUsing: (fileKey, file, success, error, progress) => {
                            $wire.upload(
                                `{{ $statePath }}.${fileKey}`,
                                file,
                                () => { success(fileKey) },
                                error,
                                (progressEvent) => {
                                    progress(true, progressEvent.detail.progress, 100)
                                },
                            )
                        },
                    })"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled])), 0, 64) }}"
            {{
                $attributes
                    ->merge([
                        'aria-labelledby' => "{$id}-label",
                        'id'              => $id,
                        'role'            => 'group',
                    ], escape: false)
                    ->merge($getExtraAttributes(), escape: false)
                    ->merge($getExtraAlpineAttributes(), escape: false)
                    ->class([
                        'fi-fo-file-upload flex flex-col gap-y-2 [&_.filepond--root]:font-sans',
                        match ($alignment) {
                            Alignment::Start, Alignment::Left => 'items-start',
                            Alignment::Center => 'items-center',
                            Alignment::End, Alignment::Right => 'items-end',
                            default => $alignment,
                        },
                    ])
            }}
        >
            <div @class(['h-full', 'w-32' => $isAvatar, 'w-full' => ! $isAvatar])>
                <input
                    x-ref="input"
                    {{
                        $getExtraInputAttributeBag()
                            ->merge([
                                'aria-labelledby' => "{$id}-label",
                                'disabled'        => $isDisabled,
                                'multiple'        => $isMultiple(),
                                'type'            => 'file',
                            ], escape: false)
                    }}
                />
            </div>

            <div
                x-show="error"
                x-text="error"
                x-cloak
                class="text-sm text-danger-600 dark:text-danger-400"
            ></div>

        </div>
        {{-- end wire:ignore FileUpload --}}

    </div>
    {{-- end caption wrapper --}}

</x-dynamic-component>
