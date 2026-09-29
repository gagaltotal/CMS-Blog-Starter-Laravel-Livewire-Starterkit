<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    /**
     * Tailwind classes for the little status pill shown in the admin list.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-[#F3ECDD] text-[#8A6A2F]',
            self::Published => 'bg-[#E4EFEE] text-[#1F4B4A]',
        };
    }
}
