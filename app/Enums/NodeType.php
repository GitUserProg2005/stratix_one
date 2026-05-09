<?php

namespace App\Enums;


enum NodeType: string
{
    case WEBHOOK_TRIGGER = 'webhook_trigger';   
    case AI_REQUEST = 'ai_request';
    case AI_AGENT_REQUEST = 'ai_agent_request';
    case EMAIL_REPORT = 'email_report';
    case OSRM = 'osrm';
    case LOG = 'log';
    case COLLECT_METRICS = 'collect_metrics';
    case UPDATE_METRIC = 'update_metric';
    case CONDITION = 'condition';
    case SCHEDULE = 'schedule';
}
