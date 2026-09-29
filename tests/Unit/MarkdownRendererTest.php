<?php

namespace Tests\Unit;

use App\Services\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test: MarkdownRenderer needs the CommonMark library but not a
 * booted Laravel application, so it extends PHPUnit's TestCase directly.
 */
class MarkdownRendererTest extends TestCase
{
    public function test_it_renders_regular_markdown(): void
    {
        $html = MarkdownRenderer::toHtml("# Title\n\nSome **bold** text.");

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_raw_html_is_stripped(): void
    {
        $html = MarkdownRenderer::toHtml("<script>alert(1)</script>\n\n<b onclick=\"x()\">hi</b>");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_unsafe_link_schemes_are_neutralised(): void
    {
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'vbscript:msgbox(1)', 'data:text/html;base64,PHNjcmlwdD4='] as $bad) {
            $html = MarkdownRenderer::toHtml("[x]({$bad})");

            $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
            $this->assertStringNotContainsStringIgnoringCase('vbscript:', $html);
            $this->assertStringNotContainsString('data:text/html', $html);
        }
    }

    public function test_excerpt_is_plain_text_and_truncated(): void
    {
        $excerpt = MarkdownRenderer::toExcerpt('# Heading '.str_repeat('word ', 100), 50);

        $this->assertStringNotContainsString('<', $excerpt);
        $this->assertLessThanOrEqual(53, mb_strlen($excerpt)); // 50 + "..."
    }

    public function test_null_and_empty_input_are_safe(): void
    {
        $this->assertSame('', trim(MarkdownRenderer::toHtml(null)));
        $this->assertSame('', MarkdownRenderer::toExcerpt(''));
    }
}
