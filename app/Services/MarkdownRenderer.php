<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * The ONE place Markdown becomes HTML in this application.
 *
 * Everything that renders user-authored Markdown (the public post page,
 * the live preview in the admin editor, auto-generated excerpts) goes
 * through here, so the XSS-relevant settings live in a single, auditable
 * spot instead of being repeated (and possibly forgotten) at each call
 * site.
 *
 *  - html_input = strip        : raw HTML in the source is removed entirely,
 *                                so <script>, <iframe>, onerror= etc. never
 *                                reach the page.
 *  - allow_unsafe_links = false: javascript:, vbscript: and data: URLs in
 *                                links/images are neutralised.
 *  - max_nesting_level         : caps recursion depth so a maliciously
 *                                nested document can't exhaust the parser.
 */
class MarkdownRenderer
{
    public static function toHtml(?string $markdown): string
    {
        return (string) Str::markdown((string) $markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }

    public static function toExcerpt(?string $markdown, int $limit = 160): string
    {
        $text = Str::of(strip_tags(static::toHtml($markdown)))->squish()->toString();

        return Str::limit($text, $limit);
    }
}
