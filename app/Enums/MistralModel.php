<?php

namespace App\Enums;


enum MistralModel: string
{
    case SMALL_LATEST = 'mistral-small-latest';
    case MEDIUM_LATEST = 'mistral-medium-latest';
    case LARGE_LATEST = 'mistral-large-latest';
    case MINISTRAL_8B = 'ministral-8b-latest';
    case MINISTRAL_14B = 'ministral-14b-latest';
    case PIXTRAL_LARGE = 'pixtral-large-latest';
    case PIXTRAL_12B = 'pixtral-12b-2409';
    case OCR_LATEST = 'mistral-ocr-latest';

    public function task(): MistralTask
    {
        return match ($this) {
            self::OCR_LATEST => MistralTask::OCR,
            self::PIXTRAL_LARGE, self::PIXTRAL_12B => MistralTask::PICTURE,
            default => MistralTask::TEXT,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SMALL_LATEST => 'Mistral Small',
            self::MEDIUM_LATEST => 'Mistral Medium',
            self::LARGE_LATEST => 'Mistral Large',
            self::MINISTRAL_8B => 'Ministral 8B',
            self::MINISTRAL_14B => 'Ministral 14B',
            self::PIXTRAL_LARGE => 'Pixtral Large',
            self::PIXTRAL_12B => 'Pixtral 12B',
            self::OCR_LATEST => 'Mistral OCR',
        };
    }

    public static function defaultFor(MistralTask $task): self
    {
        return match ($task) {
            MistralTask::OCR => self::OCR_LATEST,
            MistralTask::PICTURE => self::PIXTRAL_LARGE,
            MistralTask::TEXT => self::SMALL_LATEST,
        };
    }

    /**
     * @return list<self>
     */
    public static function forTask(MistralTask $task): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $model) => $model->task() === $task
        ));
    }
}
