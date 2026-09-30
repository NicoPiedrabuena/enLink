<?php

namespace App\Support;

class PublicPageCache
{
    public static function key(string $username): string
    {
        return 'public-page:'.strtolower($username);
    }
}
