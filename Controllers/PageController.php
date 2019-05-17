<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Page;
use AppBundle\Entity\PageBlock;
use AppBundle\Entity\Traits\ServicePricingTrait;
use AppBundle\Service\SiteSection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PageController extends Controller
{
    use ServicePricingTrait;

    public function showAction($section, $slug, Request $request)
    {
        $locale = $request->getLocale();

        /* @var $page Page */
        $page = $this->getDoctrine()
                ->getRepository('AppBundle:Page')
                ->getPage($locale, $section, $slug);

        if (!$page) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted('view', $page)) {
            throw $this->createNotFoundException();
        }

        $viewData = $this->getViewData($page, $request);
        return $this->render($this->getPageTemplate($page->getType()), $viewData);
    }

    protected function getViewData(Page $page, Request $request)
    {
        $viewData = [
            'page' => $page,
            'block_data' => []
        ];

        $section = $page->getSection() ?? SiteSection::SECTION_PREMIUM;

        foreach ($page->getBlocks() as $block) {
            $blockKey = 'block_' . $block->getId();
            $blockViewData = [];

            switch ($block->getType()) {
                case PageBlock::PAGE_BLOCK_LATEST_POSTS:
                    $blockViewData = $this->getViewDataForLatestPostsBlock($section, $request->getLocale());
                    break;
                case PageBlock::PAGE_BLOCK_JOBS:
                    $blockViewData = $this->getViewDataForJobsBlock();
                    break;
                case PageBlock::PAGE_BLOCK_SERVICES:
                    $blockViewData = $this->getViewDataForServicesBlock();
                    break;
                case PageBlock::PAGE_BLOCK_SERVICE_CATEGORIES:
                    $blockViewData = $this->getViewDataForServiceCategoriesBlock();
                    break;
                default:
                    break;
            }

            $blockViewData['block'] = $block;
            $viewData['block_data'][$blockKey] = $blockViewData;
        }

        if ($page->getType() == Page::PAGE_TYPE_NEWS) {
            $viewData = array_merge($viewData, $this->getViewDataForNewsPage($section, $request));
        }

        return $viewData;
    }

    protected function getPageTemplate($pageType)
    {
        return 'AppBundle:Page:show_' . $pageType . '.html.twig';
    }

    protected function getViewDataForNewsPage($section, Request $request)
    {
        $locale = $request->getLocale();
        $perPage = 20;
        $paginatorPage = $request->query->getInt('page', 1);

        if ($paginatorPage < 1) {
            $paginatorPage = 1;
        }

        $news = $this->getDoctrine()
                ->getRepository('AppBundle:BlogPost')
                ->getPaged($locale, $section, $paginatorPage, $perPage);

        $newsCount = $this->getDoctrine()
                ->getRepository('AppBundle:BlogPost')
                ->count($locale, $section);

        $totalPages = ceil($newsCount / $perPage);

        return [
            'news' => $news,
            'total_pages' => $totalPages,
            'paginator_page' => $paginatorPage,
        ];
    }

    protected function getViewDataForJobsBlock()
    {
        $jobs = $this->getDoctrine()
                ->getRepository('AppBundle:JobAd')
                ->getAllOrdered();

        return [
            'jobs' => $jobs
        ];
    }

    protected function getViewDataForServicesBlock()
    {
        $services = $this->getDoctrine()
                ->getRepository('AppBundle:Service')
                ->getAllEnabledSorted();

        foreach ($services as $service) {
            $this->setServicePrice($service, true);
        }

        return [
            'services' => $services
        ];
    }

    protected function getViewDataForServiceCategoriesBlock()
    {
        $serviceCategories = $this->getDoctrine()
                ->getRepository('AppBundle:ServiceCategory')
                ->getBySortableGroups();

        return [
            'service_categories' => $serviceCategories
        ];
    }

    protected function getViewDataForLatestPostsBlock($section, $locale)
    {
        $latestPosts = $this->getDoctrine()
                ->getRepository('AppBundle:BlogPost')
                ->getLatest($locale, $section, 4);

        return [
            'latest_posts' => $latestPosts
        ];
    }

}
