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
    case PAGE_LOADER = 'page_loader';
    case GO_WHISPER = 'go_whisper';
    case MISTRAL_TEXT = 'mistral_text';
    case MISTRAL_PICTURE = 'mistral_picture';
    case MISTRAL_OCR = 'mistral_ocr';
    case POINT_IN_POLYGON = 'point_in_polygon';
    case HTTP_CALLBACK = 'http_callback';
}
