<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Started = 'started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
