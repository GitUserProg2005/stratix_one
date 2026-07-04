<?php

namespace App\Enums;

enum FeatureType: string
{
    case DASHBOARD = 'dashboard';
    case CRM = 'crm';
    case AI_AGENT = 'ai_agent';
    case TASK_MANAGER = 'task_manager';
}
