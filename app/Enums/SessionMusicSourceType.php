<?php

namespace App\Enums;

enum SessionMusicSourceType: string
{
    case Uploaded = 'uploaded';
    case DirectUrl = 'direct_url';
    case Youtube = 'youtube';
}
