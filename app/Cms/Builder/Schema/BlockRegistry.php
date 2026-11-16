<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

/**
 * The single source of truth for what a block is.
 *
 * For every BlockType this answers three questions:
 *   - what props does a freshly dropped block start with?
 *   - may it hold children, and which types?
 *   - is it a container (structural) or a leaf?
 *
 * The builder palette, the properties panel, the document validator and the
 * renderer all read from here, so adding a block type is a one-file change.
 */
final class BlockRegistry
{
    /**
     * @return array<string, array{
     *     defaults: array<string, mixed>,
     *     container: bool,
     *     allowedChildren: list<string>|null,
     *     maxChildren: int|null
     * }>
     */
    public static function all(): array
    {
        static $registry = null;

        return $registry ??= self::build();
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultProps(BlockType $type): array
    {
        return self::all()[$type->value]['defaults'];
    }

    public static function isContainer(BlockType $type): bool
    {
        return self::all()[$type->value]['container'];
    }

    public static function maxChildren(BlockType $type): ?int
    {
        return self::all()[$type->value]['maxChildren'];
    }

    /**
     * Null means "any block type". An empty list means "no children at all".
     *
     * @return list<BlockType>|null
     */
    public static function allowedChildren(BlockType $type): ?array
    {
        $allowed = self::all()[$type->value]['allowedChildren'];

        if ($allowed === null) {
            return null;
        }

        return array_map(static fn (string $value): BlockType => BlockType::from($value), $allowed);
    }

    public static function accepts(BlockType $parent, BlockType $child): bool
    {
        if (! self::isContainer($parent)) {
            return false;
        }

        $allowed = self::allowedChildren($parent);

        return $allowed === null || in_array($child, $allowed, strict: true);
    }

    /**
     * @return array<string, array{
     *     defaults: array<string, mixed>,
     *     container: bool,
     *     allowedChildren: list<string>|null,
     *     maxChildren: int|null
     * }>
     */
    private static function build(): array
    {
        return [
            BlockType::Container->value => self::container([
                'layout' => 'stack',
                'gap' => '16px',
            ], allowedChildren: null),

            BlockType::Heading->value => self::leaf([
                'text' => 'Your headline goes here',
                'level' => 'h2',
                'link' => null,
            ]),

            BlockType::Paragraph->value => self::leaf([
                // Portable inline markup: a restricted subset (b, i, u, a, br)
                // sanitised on save. Not a full HTML document.
                'text' => 'Write something that makes the next click obvious.',
            ]),

            BlockType::Image->value => self::leaf([
                'src' => null,
                'alt' => '',
                'caption' => null,
                'link' => null,
                'loading' => 'lazy',
                'objectFit' => 'cover',
            ]),

            BlockType::Video->value => self::leaf([
                'presentation' => 'inline', // inline | modal
                'provider' => 'youtube', // youtube | vimeo | wistia | file
                'url' => null,
                'posterSrc' => null,
                'autoplay' => false,
                'muted' => true,
                'loop' => false,
                'controls' => true,
                'aspectRatio' => '16:9',
            ]),

            BlockType::Button->value => self::leaf([
                'label' => 'Get started',
                'action' => 'url', // url | next_step | submit_form | scroll_to | popup
                'url' => null,
                'targetStepId' => null,
                'targetBlockId' => null,
                'newTab' => false,
                'iconLeft' => null,
                'iconRight' => null,
                'fullWidth' => false,
                'size' => 'md',
                'variant' => 'primary',
            ]),

            BlockType::Icon->value => self::leaf([
                'name' => 'star',
                'library' => 'lucide',
                'size' => '24px',
                'link' => null,
            ]),

            BlockType::Divider->value => self::leaf([
                'style' => 'solid',
                'thickness' => '1px',
                'width' => '100%',
            ]),

            BlockType::Spacer->value => self::leaf([
                'height' => '48px',
            ]),

            BlockType::Html->value => self::leaf([
                'html' => '',
            ]),

            BlockType::CustomCode->value => self::leaf([
                'html' => '',
                'css' => '',
                'js' => '',
                'placement' => 'inline', // inline | head | body_end
            ]),

            BlockType::SiteForm->value => self::leaf([
                'formSlug' => '',
            ]),

            BlockType::Form->value => self::leaf([
                'formId' => null,
                'consentText' => null,
                'submitLabel' => 'Submit',
                'successAction' => 'message', // message | redirect | next_step
                'targetStepId' => null,
                'successMessage' => 'Thanks — we got it.',
                'redirectUrl' => null,
                'layout' => 'stacked',
            ]),

            BlockType::EmailOptin->value => self::leaf([
                'formId' => null,
                'label' => 'Email address',
                'showLabel' => false,
                'fieldName' => 'email',
                'required' => true,
                'successAction' => 'message',
                'targetStepId' => null,
                'successMessage' => 'Thanks — we got it.',
                'redirectUrl' => null,
                'placeholder' => 'you@example.com',
                'submitLabel' => 'Send it to me',
                'consentText' => null,
                'tagIds' => [],
                'layout' => 'inline',
            ]),

            BlockType::PhoneInput->value => self::leaf([
                'formId' => null,
                'label' => 'Phone number',
                'showLabel' => true,
                'fieldName' => 'phone',
                'required' => true,
                'successAction' => 'message',
                'targetStepId' => null,
                'successMessage' => 'Thanks — we got it.',
                'redirectUrl' => null,
                'placeholder' => '+1 555 000 0000',
                'defaultCountry' => 'US',
                'submitLabel' => 'Text me',
                'requireConsent' => true,
                'consentText' => 'I agree to receive text messages. Reply STOP to opt out.',
                'layout' => 'inline',
            ]),

            BlockType::Checkout->value => self::leaf([
                'productIds' => [],
                'showOrderSummary' => true,
                'collectBillingAddress' => false,
                'collectPhone' => false,
                // Needed by any one-click upsell button on a later step.
                'saveCardForUpsells' => true,
                'submitLabel' => 'Complete order',
                'paymentMethods' => ['card'],
            ]),

            BlockType::Product->value => self::leaf([
                'productId' => null,
                'showPrice' => true,
                'showImage' => true,
                'showDescription' => true,
                'buttonLabel' => 'Buy now',
            ]),

            BlockType::PricingTable->value => self::leaf([
                'plans' => [
                    [
                        'name' => 'Starter',
                        'price' => '$29',
                        'interval' => 'month',
                        'features' => ['Everything to launch', 'Email support'],
                        'buttonLabel' => 'Choose Starter',
                        'buttonUrl' => null,
                        'highlighted' => false,
                    ],
                ],
                'columns' => 3,
            ]),

            BlockType::Countdown->value => self::leaf([
                'mode' => 'fixed', // fixed | evergreen | daily
                'endsAt' => null,
                'durationMinutes' => 60,
                'expiredAction' => 'hide', // hide | message | redirect
                'expiredMessage' => 'This offer has closed.',
                'redirectUrl' => null,
                'showLabels' => true,
            ]),

            BlockType::Testimonial->value => self::leaf([
                'quote' => 'This changed how we launch.',
                'authorName' => 'Jordan Reyes',
                'authorTitle' => 'Founder, Northwind',
                'avatarSrc' => null,
                'rating' => 5,
                'layout' => 'card',
            ]),

            BlockType::Faq->value => self::leaf([
                'initialOpen' => true,
                'items' => [
                    ['question' => 'How long does setup take?', 'answer' => 'About ten minutes.'],
                ],
                'allowMultipleOpen' => false,
                'emitSchemaOrg' => true,
            ]),

            BlockType::Accordion->value => self::container([
                'items' => [
                    ['id' => null, 'title' => 'Section one'],
                ],
                'allowMultipleOpen' => false,
            ], allowedChildren: null),

            BlockType::Tabs->value => self::container([
                'tabs' => [
                    ['id' => null, 'label' => 'Tab one'],
                ],
                'orientation' => 'horizontal',
            ], allowedChildren: null),

            BlockType::ProgressBar->value => self::leaf([
                'value' => 40,
                'max' => 100,
                'label' => 'Step 2 of 5',
                'showPercentage' => false,
                'source' => 'manual', // manual | funnel_step
            ]),

            BlockType::FeatureCards->value => self::leaf([
                'items' => [
                    ['icon' => 'zap', 'title' => 'Fast', 'body' => 'Ships in a day.'],
                    ['icon' => 'shield', 'title' => 'Safe', 'body' => 'Encrypted at rest.'],
                    ['icon' => 'heart', 'title' => 'Loved', 'body' => 'Rated 4.9 by users.'],
                ],
                'columns' => 3,
            ]),

            BlockType::Carousel->value => self::container([
                'autoplay' => false,
                'intervalMs' => 5000,
                'loop' => true,
                'showArrows' => true,
                'showDots' => true,
                'slidesPerView' => 1,
            ], allowedChildren: null),

            BlockType::Gallery->value => self::leaf([
                'images' => [],
                'columns' => 3,
                'gap' => '12px',
                'lightbox' => true,
            ]),

            BlockType::SocialIcons->value => self::leaf([
                'networks' => [
                    ['network' => 'x', 'url' => null],
                    ['network' => 'instagram', 'url' => null],
                ],
                'size' => '20px',
                'shape' => 'circle',
            ]),

            BlockType::Navigation->value => self::leaf([
                'items' => [
                    ['label' => 'Home', 'url' => '/'],
                ],
                'orientation' => 'horizontal',
                'mobileBehaviour' => 'drawer',
            ]),

            BlockType::Logo->value => self::leaf([
                'src' => null,
                'alt' => null,
                'url' => '/',
                'height' => 36,
            ]),

            BlockType::Map->value => self::leaf([
                'provider' => 'osm',
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'zoom' => 14,
                'showMarker' => true,
            ]),

            BlockType::Calendar->value => self::leaf([
                'serviceId' => null,
                'confirmationAction' => 'message',
                'confirmationMessage' => 'You are booked in.',
                'targetStepId' => null,
                'view' => 'month',
                'timezoneMode' => 'visitor',
            ]),

            BlockType::Booking->value => self::leaf([
                'serviceId' => null,
                'staffId' => null,
                'showStaffPicker' => true,
                'confirmationAction' => 'message',
                'targetStepId' => null,
                'confirmationMessage' => 'You are booked in.',
            ]),

            BlockType::PopupTrigger->value => self::leaf([
                'popupId' => null,
                'trigger' => 'click', // click | scroll | exit_intent | delay
                'delaySeconds' => 5,
                'scrollPercent' => 50,
                'label' => 'Open',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array{defaults: array<string, mixed>, container: bool, allowedChildren: list<string>|null, maxChildren: int|null}
     */
    private static function leaf(array $defaults): array
    {
        return [
            'defaults' => $defaults,
            'container' => false,
            'allowedChildren' => [],
            'maxChildren' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  list<BlockType>|null  $allowedChildren
     * @return array{defaults: array<string, mixed>, container: bool, allowedChildren: list<string>|null, maxChildren: int|null}
     */
    private static function container(array $defaults, ?array $allowedChildren, ?int $maxChildren = null): array
    {
        return [
            'defaults' => $defaults,
            'container' => true,
            'allowedChildren' => $allowedChildren === null
                ? null
                : array_map(static fn (BlockType $type): string => $type->value, $allowedChildren),
            'maxChildren' => $maxChildren,
        ];
    }
}
