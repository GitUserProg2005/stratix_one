<?php

namespace App\Enums;

enum MistralTask: string
{
    case TEXT = 'text';
    case PICTURE = 'picture';
    case OCR = 'ocr';
}
