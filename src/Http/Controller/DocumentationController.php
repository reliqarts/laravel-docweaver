<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Contract\Documentation\Provider;
use ReliqArts\Docweaver\Contract\Logger;
use ReliqArts\Docweaver\Contract\Product\Finder;
use ReliqArts\Docweaver\Model\Product;
use Symfony\Component\DomCrawler\Crawler;

class DocumentationController
{
    private const DEFAULT_PAGE = 'installation';

    /**
     * @var ConfigProvider
     */
    protected ConfigProvider $configProvider;

    /**
     * The documentation repository.
     *
     * @var Provider
     */
    protected Provider $documentationProvider;

    /**
     * Doc home path.
     *
     * @var string
     */
    protected string $documentationHome;

    /**
     * @var Finder
     */
    protected Finder $productFinder;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * Create a new controller instance.
     */
    public function __construct(ConfigProvider $configProvider, Logger $logger, Provider $docs, Finder $productFinder)
    {
        $this->logger = $logger;
        $this->documentationProvider = $docs;
        $this->productFinder = $productFinder;
        $this->documentationHome = $configProvider->getRoutePrefix();
        $this->configProvider = $configProvider;
    }

    /**
     * Show the documentation home page (docs).
     */
    public function index(): View
    {
        $templateConfig = $this->configProvider->getTemplateConfig();
        $title = $templateConfig->getIndexTitle();
        $products = $this->productFinder->listProducts();

        return view('docweaver::index', [
            'title' => $title,
            'products' => $products,
        ]);
    }

    /**
     * Show the index page for a product (docs/foo).
     *
     * @param mixed $productName
     */
    public function productIndex(string $productName): RedirectResponse
    {
        $product = $this->productFinder->findProduct($productName);

        if ($product === null || $product->getDefaultVersion() === Product::VERSION_UNKNOWN) {
            abort(404);
        }

        // route to default version
        return redirect()->route($this->configProvider->getProductPageRouteName(), [
            $product->getKey(),
            $product->getDefaultVersion(),
        ]);
    }

    /**
     * Show a documentation page.
     *
     * @param string $page
     *
     * @return RedirectResponse|View
     */
    public function show(string $productKey, string $version, string $page = null)
    {
        // ensure product exists
        $product = $this->productFinder->findProduct($productKey);
        if ($product === null) {
            abort(404);
        }

        // get default version for product
        $defaultVersion = $product->getDefaultVersion();
        if (!$product->hasVersion($version)) {
            return redirect(route(
                $this->configProvider->getProductPageRouteName(),
                [$product->getKey(), $defaultVersion]
            ), 301);
        }

        // get page content
        $page = $page ?: self::DEFAULT_PAGE;
        $content = $this->documentationProvider->getPage($product, $version, $page);

        // ensure page has content
        if (empty($content)) {
            $this->logger->warning(
                sprintf('Documentation page (%s) for %s has no content.', $page, $product->getName()),
                ['product' => $product]
            );
            abort(404);
        }

        $title = (new Crawler($content))->filterXPath('//h1');
        $section = '';

        // ensure section exists
        if ($this->documentationProvider->sectionExists($product, $version, $page)) {
            $section .= "/${page}";
        } elseif (!empty($page)) {
            // section does not exist, go to version index
            return redirect()->route($this->configProvider->getProductPageRouteName(), [$product->getKey(), $version]);
        }

        // set canonical
        $canonical = null;
        if ($this->documentationProvider->sectionExists($product, $defaultVersion, $page)) {
            $canonical = route(
                $this->configProvider->getProductPageRouteName(),
                [$product->getKey(), $defaultVersion, $page]
            );
        }

        return view('docweaver::page', [
            'canonical' => $canonical,
            'content' => $content,
            'currentProduct' => $product,
            'currentSection' => $section,
            'currentVersion' => $version,
            'index' => $this->documentationProvider->getPage($product, $version),
            'page' => $page,
            'title' => count($title) ? $title->text() : null,
            'versions' => $product->getVersions(),
        ]);
    }
}
