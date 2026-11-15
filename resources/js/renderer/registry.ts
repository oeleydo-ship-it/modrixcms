/**
 * BlockType -> component registry.
 *
 * Every case of `App\Domain\Pages\Enums\BlockType` resolves to a component; the
 * `Record<BlockType, BlockComponent>` type makes a missing entry a compile
 * error rather than a blank spot on a published page.
 */

import {
    ButtonBlock,
    CarouselBlock,
    ContainerBlock,
    CustomCodeBlock,
    SiteFormBlock,
    DividerBlock,
    GalleryBlock,
    HeadingBlock,
    HtmlBlock,
    IconBlock,
    ImageBlock,
    LogoBlock,
    MapBlock,
    NavigationBlock,
    ParagraphBlock,
    SocialIconsBlock,
    SpacerBlock,
    VideoBlock,
} from './blocks/content';
import {
    AccordionBlock,
    BookingBlock,
    CalendarBlock,
    CheckoutBlock,
    CountdownBlock,
    EmailOptinBlock,
    FaqBlock,
    FeatureCardsBlock,
    FormBlock,
    PhoneInputBlock,
    PopupTriggerBlock,
    PricingTableBlock,
    ProductBlock,
    ProgressBarBlock,
    TabsBlock,
    TestimonialBlock,
} from './blocks/interactive';
import type { BlockComponent } from './blocks/shared';
import type { BlockType } from './types';

export const blockRegistry: Record<BlockType, BlockComponent> = {
    container: ContainerBlock,
    heading: HeadingBlock,
    paragraph: ParagraphBlock,
    image: ImageBlock,
    video: VideoBlock,
    button: ButtonBlock,
    icon: IconBlock,
    divider: DividerBlock,
    spacer: SpacerBlock,
    html: HtmlBlock,
    custom_code: CustomCodeBlock,
    site_form: SiteFormBlock,
    form: FormBlock,
    email_optin: EmailOptinBlock,
    phone_input: PhoneInputBlock,
    checkout: CheckoutBlock,
    product: ProductBlock,
    pricing_table: PricingTableBlock,
    countdown: CountdownBlock,
    testimonial: TestimonialBlock,
    faq: FaqBlock,
    accordion: AccordionBlock,
    tabs: TabsBlock,
    progress_bar: ProgressBarBlock,
    feature_cards: FeatureCardsBlock,
    carousel: CarouselBlock,
    gallery: GalleryBlock,
    social_icons: SocialIconsBlock,
    navigation: NavigationBlock,
    logo: LogoBlock,
    map: MapBlock,
    calendar: CalendarBlock,
    booking: BookingBlock,
    popup_trigger: PopupTriggerBlock,
};

export function componentFor(type: BlockType): BlockComponent {
    return blockRegistry[type];
}

/** Block types whose text can be edited straight on the canvas. */
export const INLINE_EDITABLE: ReadonlySet<BlockType> = new Set<BlockType>(['heading', 'paragraph', 'button']);
