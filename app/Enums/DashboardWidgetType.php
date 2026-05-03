<?php

namespace App\Enums;

enum DashboardWidgetType: string
{
    case CHART = 'chart';
    case INSIGHT = 'insight';
    case HISTORY = 'history';
}
