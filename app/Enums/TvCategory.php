<?php

declare(strict_types=1);

namespace App\Enums;

enum TvCategory: string
{
    case TELEVIZORJI = 'Televizorji';
    case TV_DODATKI  = 'TV dodatki';

    /**
     * Leaf categories belonging to the "TV sprejemniki" group.
     *
     * @return self[]
     */
    public static function tvReceiversLeaf(): array
    {
        return [
            self::TELEVIZORJI,
            self::TV_DODATKI,
        ];
    }
}
