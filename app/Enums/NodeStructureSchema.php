<?php

namespace App\Enums;


enum NodeStructureSchema: string {
    case STATIC = 'static';
    case DYNAMIC = 'dynamic';
}