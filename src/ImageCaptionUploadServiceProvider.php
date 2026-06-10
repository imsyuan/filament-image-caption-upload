<?php

namespace Imsyuan\ImageCaptionUpload;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ImageCaptionUploadServiceProvider extends PackageServiceProvider
{
    public static string $name = 'image-caption-upload';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasViews()->hasTranslations();
    }
}
