<?php

namespace Modrix\FormBuilder\Http;

use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\BuildsDocumentNodes;
use App\Cms\Builder\SiteChrome;
use App\Cms\Frontend\BlockPresenter;
use App\Http\Controllers\PageController;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Response;
use Modrix\FormBuilder\Models\Form;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * A form's own public page: /forms/{slug}. With ?embed=1 it is drawn without the site header and
 * footer, which is what the embed code (an iframe) points at.
 */
class PublicFormController
{
    public function __invoke(Request $request, string $slug, BlockPresenter $presenter, Settings $settings, SiteChrome $chrome): Response|View|SymfonyResponse
    {
        $form = Form::query()->active()->where('slug', $slug)->firstOrFail();
        $embed = $request->boolean('embed');

        // On the public page the form sits inside the active theme's own header and footer (the ones
        // built for it in the page builder), so it looks like every other page of that theme.
        if (! $embed && ($chrome->sections(Page::ROLE_HEADER) !== [] || $chrome->sections(Page::ROLE_FOOTER) !== [])) {
            return $this->withThemeChrome($form, $settings);
        }

        $blocks = $presenter->present([[
            'id' => 'form-'.$form->id,
            'type' => 'modrix/form-builder:form',
            'data' => ['form_slug' => $form->slug, 'layout' => 'card', 'anchor' => 'form'],
        ]]);

        return inertia('Site/Page', [
            'page' => ['title' => $form->name, 'slug' => 'forms/'.$form->slug],
            'blocks' => $blocks,
            'embed' => $embed,
            'meta' => [
                'title' => $form->name.' · '.$settings->get('site_name'),
                'description' => $form->description ?: null,
            ],
        ]);
    }

    private function withThemeChrome(Form $form, Settings $settings): View|SymfonyResponse
    {
        $nodes = new class
        {
            use BuildsDocumentNodes;

            /** @return array<string, mixed> */
            public function formSection(string $slug): array
            {
                return $this->section([
                    $this->row([
                        $this->column(12, [$this->blockNode(BlockType::SiteForm, ['formSlug' => $slug])]),
                    ]),
                ], ['padding' => ['top' => '56px', 'bottom' => '72px']]);
            }
        };

        $page = new Page([
            'title' => $form->name,
            'slug' => 'forms/'.$form->slug,
            'meta_title' => $form->name.' · '.$settings->get('site_name'),
            'meta_description' => $form->description ?: null,
            'show_site_header' => true,
            'show_site_footer' => true,
        ]);
        $page->published_document = [...PageDocuments::blank(), 'sections' => [$nodes->formSection($form->slug)]];

        return PageController::builderPage($page);
    }
}
