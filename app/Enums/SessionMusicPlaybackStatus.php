<?php

namespace App\Enums;

enum SessionMusicPlaybackStatus: string
{
    case Playing = 'playing';
    case Paused = 'paused';
    case Stopped = 'stopped';
}
