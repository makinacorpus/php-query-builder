<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

enum JoinMode
{
    case Cross;
    case Full;
    case FullOuter;
    case Inner;
    case Lateral;
    case Left;
    case LeftOuter;
    case Natural;
    case Right;
    case RightOuter;
}
