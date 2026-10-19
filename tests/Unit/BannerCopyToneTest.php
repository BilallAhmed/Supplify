<?php

namespace Tests\Unit;

use App\Support\BannerCopyTone;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BannerCopyToneTest extends TestCase
{
    #[Test]
    public function it_detects_dark_copy_column_on_teal_banner(): void
    {
        $this->assertSame('dark', BannerCopyTone::detect('/images/homepage/Hero-Banner-Teal.png'));
    }

    #[Test]
    public function it_detects_light_copy_column_on_light_banners(): void
    {
        $this->assertSame('light', BannerCopyTone::detect('/images/homepage/Hero-Banner.png'));
        $this->assertSame('light', BannerCopyTone::detect('/images/homepage/supplify-hero-pearl-aqua-v2.png'));
    }

    #[Test]
    public function it_defaults_to_dark_when_image_is_missing(): void
    {
        $this->assertSame('dark', BannerCopyTone::detect('/images/homepage/does-not-exist.png'));
        $this->assertSame('dark', BannerCopyTone::detect(null));
    }
}

