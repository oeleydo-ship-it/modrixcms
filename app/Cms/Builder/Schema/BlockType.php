<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

/**
 * The 33 block types of the page document (spec §5).
 *
 * `container` is the one structural block: it is the only block that may hold
 * other blocks, which is what makes nested layout inside a column possible.
 * Everything else is a leaf. The authoritative default props, allowed children
 * and container flag live in App\Cms\Builder\Schema\BlockRegistry — this enum
 * is only the vocabulary.
 */
enum BlockType: string
{
    // Layout
    case Container = 'container';

    // Content
    case Heading = 'heading';
    case Paragraph = 'paragraph';
    case Image = 'image';
    case Video = 'video';
    case Button = 'button';
    case Icon = 'icon';
    case Divider = 'divider';
    case Spacer = 'spacer';
    case Html = 'html';
    case CustomCode = 'custom_code';

    // Capture
    case Form = 'form';
    case SiteForm = 'site_form';
    case EmailOptin = 'email_optin';
    case PhoneInput = 'phone_input';

    // Commerce
    case Checkout = 'checkout';
    case Product = 'product';
    case PricingTable = 'pricing_table';

    // Persuasion
    case Countdown = 'countdown';
    case Testimonial = 'testimonial';
    case Faq = 'faq';
    case Accordion = 'accordion';
    case Tabs = 'tabs';
    case ProgressBar = 'progress_bar';
    case FeatureCards = 'feature_cards';

    // Media and navigation
    case Carousel = 'carousel';
    case Gallery = 'gallery';
    case SocialIcons = 'social_icons';
    case Navigation = 'navigation';
    case Logo = 'logo';
    case Map = 'map';

    // Scheduling
    case Calendar = 'calendar';
    case Booking = 'booking';

    // Engagement
    case PopupTrigger = 'popup_trigger';

    public function label(): string
    {
        return match ($this) {
            self::Container => 'Container',
            self::Heading => 'Heading',
            self::Paragraph => 'Paragraph',
            self::Image => 'Image',
            self::Video => 'Video',
            self::Button => 'Button',
            self::Icon => 'Icon',
            self::Divider => 'Divider',
            self::Spacer => 'Spacer',
            self::Html => 'HTML embed',
            self::CustomCode => 'Custom code / script',
            self::Form => 'Form',
            self::SiteForm => 'Saved form',
            self::EmailOptin => 'Email opt-in',
            self::PhoneInput => 'Phone input',
            self::Checkout => 'Checkout',
            self::Product => 'Product',
            self::PricingTable => 'Pricing table',
            self::Countdown => 'Countdown',
            self::Testimonial => 'Testimonial',
            self::Faq => 'FAQ',
            self::Accordion => 'Accordion',
            self::Tabs => 'Tabs',
            self::ProgressBar => 'Progress bar',
            self::FeatureCards => 'Feature cards',
            self::Carousel => 'Carousel',
            self::Gallery => 'Gallery',
            self::SocialIcons => 'Social icons',
            self::Navigation => 'Navigation',
            self::Logo => 'Logo',
            self::Map => 'Map',
            self::Calendar => 'Calendar',
            self::Booking => 'Booking',
            self::PopupTrigger => 'Popup trigger',
        };
    }

    /**
     * Builder palette grouping.
     */
    public function group(): string
    {
        return match ($this) {
            self::Container => 'layout',
            self::Heading, self::Paragraph, self::Image, self::Video, self::Button,
            self::Icon, self::Divider, self::Spacer => 'content',
            self::Html, self::CustomCode => 'code',
            self::Form, self::SiteForm, self::EmailOptin, self::PhoneInput => 'capture',
            self::Checkout, self::Product, self::PricingTable => 'commerce',
            self::Countdown, self::Testimonial, self::Faq, self::Accordion,
            self::Tabs, self::ProgressBar, self::FeatureCards => 'persuasion',
            self::Carousel, self::Gallery, self::SocialIcons, self::Navigation,
            self::Logo, self::Map => 'media',
            self::Calendar, self::Booking => 'scheduling',
            self::PopupTrigger => 'engagement',
        };
    }

    /**
     * Blocks whose output is authored HTML/JS. They are stored verbatim but the
     * renderer sandboxes them, and a policy check gates who may create them.
     */
    public function isRawOutput(): bool
    {
        return in_array($this, [self::Html, self::CustomCode], strict: true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
