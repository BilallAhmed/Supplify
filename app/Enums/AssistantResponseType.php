<?php

namespace App\Enums;

enum AssistantResponseType: string
{
    case Answer = 'answer';
    case Clarification = 'clarification';
    case Unknown = 'unknown';
    case Boundary = 'boundary';
    case Error = 'error';
}
