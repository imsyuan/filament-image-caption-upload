<?php

namespace Imsyuan\ImageCaptionUpload\Tests;

use Imsyuan\ImageCaptionUpload\ImageCaptionUploadServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ImageCaptionUploadServiceProvider::class,
        ];
    }
}
