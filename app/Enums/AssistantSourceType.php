<?php

namespace App\Enums;

enum AssistantSourceType: string
{
    case Product = 'product';
    case Post = 'post';
    case Page = 'page';
    case Category = 'category';
    case Faq = 'faq';
}
