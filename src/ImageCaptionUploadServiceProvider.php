<?php

namespace Imsyuan\ImageCaptionUpload;

use Illuminate\Support\ServiceProvider;

class ImageCaptionUploadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'image-caption-upload');
    }
}
