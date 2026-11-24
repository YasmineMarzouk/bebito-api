<?php

namespace Tests\Unit\Core;

use Modules\Core\Support\RichText;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_it_keeps_supported_formatting_without_html_attributes_or_scripts(): void
    {
        $html = RichText::sanitize('<p onclick="alert(1)"><strong>Soft</strong> cotton</p><ul><li>Breathable</li></ul><script>alert(1)</script>');

        $this->assertSame('<p><strong>Soft</strong> cotton</p><ul><li>Breathable</li></ul>', $html);
    }

    public function test_it_returns_null_for_empty_content(): void
    {
        $this->assertNull(RichText::sanitize('   '));
    }
}
