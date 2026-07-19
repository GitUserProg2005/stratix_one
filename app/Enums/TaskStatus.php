<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Started = 'started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
