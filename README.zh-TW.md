# Filament Image Caption Upload

[![Packagist 最新版本](https://img.shields.io/packagist/v/imsyuan/filament-image-caption-upload.svg?style=flat-square)](https://packagist.org/packages/imsyuan/filament-image-caption-upload)
[![GitHub 測試狀態](https://img.shields.io/github/actions/workflow/status/imsyuan/filament-image-caption-upload/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/imsyuan/filament-image-caption-upload/actions?query=workflow%3Atests+branch%3Amain)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/imsyuan/filament-image-caption-upload/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/imsyuan/filament-image-caption-upload/actions?query=workflow%3Aphpstan+branch%3Amain)
[![授權條款](https://img.shields.io/packagist/l/imsyuan/filament-image-caption-upload.svg?style=flat-square)](https://packagist.org/packages/imsyuan/filament-image-caption-upload)

[English](README.md) | **繁體中文**

一個 [Filament v3](https://filamentphp.com) 表單元件，擴展內建的 `FileUpload`，讓每張上傳的圖片下方可以附加獨立的說明文字——無需任何前端建置步驟。

![Demo](https://raw.githubusercontent.com/imsyuan/filament-image-caption-upload/main/art/demo.png)

---

## 功能特色

- **即插即用** — 直接繼承 Filament 原生 `FileUpload`，所有既有選項（`->image()`、`->multiple()`、`->reorderable()`、`->disk()`、`->directory()`、`->maxFiles()` 等）無需調整
- **零建置步驟** — 不需要 npm、Vite 或 esbuild；純 Blade + Alpine.js inline 實作
- **排序安全** — 拖曳重新排序時，說明文字會跟著對應的圖片一起移動
- **重複檔名安全** — 直接透過 FilePond 內部 ID 對應 Filament UUID，即使十個檔案都叫 `banner.jpg`，每張圖片仍各自保有獨立說明
- **競態條件安全** — 上傳完成前就輸入的說明文字會被保留，並在取得最終 UUID 後自動遷移
- **簡單儲存** — 以 `[{image, caption}]` JSON 陣列存入單一欄位，無需額外資料表或關聯

---

## 系統需求

- PHP 8.2+
- Laravel 10+
- Filament 3.x

---

## 安裝

請根據你的 Filament 版本選擇對應套件版本：

| Filament | 套件版本 |
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

Laravel 會自動探索 Service Provider，無需手動註冊。

---

## 使用方式

```php
use Imsyuan\ImageCaptionUpload\Forms\Components\ImageCaptionUpload;

ImageCaptionUpload::make('photos')
    ->multiple()
    ->image()
    ->reorderable()
    ->captionPlaceholder('輸入說明文字…'),
```

在 Model 中將欄位轉型為陣列：

```php
// app/Models/Post.php
protected $casts = [
    'photos' => 'array',
];
```

儲存後的資料格式為 `{image, caption}` 物件陣列：

```json
[
  { "image": "photos/living-room.jpg", "caption": "客廳視角" },
  { "image": "photos/kitchen.jpg",     "caption": "廚房細節" }
]
```

---

## 選項

### `captionPlaceholder(string|Closure $placeholder)`

設定每個說明輸入框的 placeholder 文字，接受純字串或 Closure，預設為 `'Caption...'`。

```php
// 靜態字串
->captionPlaceholder('新增描述…')

// 動態（多語言）
->captionPlaceholder(fn () => __('fields.caption_placeholder'))
```

### 所有原生 `FileUpload` 選項

因為 `ImageCaptionUpload` 直接繼承 Filament 的 `FileUpload`，所有內建選項均可使用：

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

## 運作原理

1. **Hydration（讀取）** — 從資料庫讀取的 `[{image, caption}]` 在載入時拆分：檔案路徑進入 Filament UUID-keyed 狀態；說明文字存入同層的 Livewire key（`_icap_<欄位名>`）。
2. **Alpine** — 透過 `MutationObserver` 監聽 FilePond item 的新增，並在每個檔案面板下方注入說明 `<input>`，輸入內容透過 `$wire.set()` 直接同步至 Livewire 狀態。
3. **UUID 對應** — 透過直接讀取 FilePond 實例（`pond.getFiles()`）將 FilePond 內部 ID 對應到 Filament UUID，無論儲存的檔名或 UUID 磁碟命名方式為何，都能正確對應。
4. **Dehydration（寫入）** — 儲存時將 `{uuid: path}` 與 `{uuid: caption}` 合併還原為 `[{image, caption}]`。

---

## 授權條款

MIT — 詳見 [LICENSE](LICENSE)。
