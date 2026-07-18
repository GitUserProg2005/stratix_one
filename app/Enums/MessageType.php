<?php

namespace App\Enums;

enum MessageType: string
{
    case ASK = 'ask';
    case AGENT = 'agent';
    case PLAN = 'plan';
    case JUDGE = 'judge';
}
