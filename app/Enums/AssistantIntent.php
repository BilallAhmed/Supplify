<?php

namespace App\Enums;

enum AssistantIntent: string
{
    case FindProduct = 'find_product';
    case PriceOrAvailability = 'price_or_availability';
    case IngredientsOrDirections = 'ingredients_or_directions';
    case RelatedArticles = 'related_articles';
    case DeliveryReturnsContact = 'delivery_returns_contact';
    case Clarify = 'clarify';
    case Unknown = 'unknown';
    case Boundary = 'boundary';

    public function label(): string
    {
        return match ($this) {
            self::FindProduct => 'Find a product',
            self::PriceOrAvailability => 'Check price or availability',
            self::IngredientsOrDirections => 'Read ingredients or directions',
            self::RelatedArticles => 'Find related articles',
            self::DeliveryReturnsContact => 'Delivery, returns, or contact',
            self::Clarify => 'Clarify an ambiguous query',
            self::Unknown => 'Unknown or unsupported',
            self::Boundary => 'Safety boundary',
        };
    }
}
