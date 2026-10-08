<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Edition;

enum Edition: string
{
    case Community = 'CE';
    case Professional = 'PE';
    case Enterprise = 'EE';
}
