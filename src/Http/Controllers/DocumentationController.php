<?php

namespace ReliQArts\Docweaver\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Log;
use ReliQArts\Docweaver\Helpers\Config;
use ReliQArts\Docweaver\Models\Documentation;
use ReliQArts\Docweaver\Models\Product;
use Symfony\Component\DomCrawler\Crawler;

class DocumentationController
{
    /**
     * Documentation configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * The documentation repository.
     *
     * @var Documentation
     */
    protected $docs;

    /**
     * Doc home path.
     *
     * @var
     */
    protected $docsHome;

    /**
     * View template information.
     *
     * @var array
     */
    protected $viewTemplateInfo;

    /**
     * Create a new controller instance.
     *
     * @param Documentation $docs
     */
    public function __construct(Documentation $docs)
    {
        $this->docs = $docs;
        $this->docsHome = Config::getRoutePrefix();
        $this->viewTemplateInfo = Config::getViewTemplateInfo();
        $this->config = Config::getConfig();
    }

    /**
     * Show the documentation home page (docs).
     *
     * @return View
     */
    public function index(): View
    {
        $title = 'Documentation';
        $products = $this->docs->listProducts();

        if (!empty($this->viewTemplateInfo['docs_title'])) {
            $title = $this->viewTemplateInfo['docs_title'];
        }

        return view('docweaver::index', [
            'title' => $title,
            'products' => $products,
            'viewTemplateInfo' => $this->viewTemplateInfo,
            'routeConfig' => $this->config['route'],
        ]);
    }

    /**
     * Show the index page for a product (docs/foo).
     *
     * @param mixed $productName
     *
     * @return RedirectResponse
     */
    public function productIndex(string $productName): RedirectResponse
    {
        $routeNames = $this->config['route']['names'];
        $product = $this->docs->getProduct($productName);

        if (!$product || $product->getDefaultVersion() === Product::UNKNOWN_VERSION) {
            abort(404);
        }

        // route to default version
        return redirect()->route($routeNames['product_page'], [
            $product->key,
            $product->getDefaultVersion(),
        ]);
    }

    /**
     * Show a documentation page.
     *
     * @param string $product
     * @param string $version
     * @param string $page
     * @param mixed  $productKey
     *
     * @return RedirectResponse|View
     */
    public function show(string $productKey, string $version, string $page = null)
    {
        $routeConfig = $this->config['route'];
        $routeNames = $routeConfig['names'];

        // ensure product exists
        $product = $this->docs->getProduct($productKey);
        if (!$product instanceof Product) {
            abort(404);
        }

        // get default version for product
        $defaultVersion = $product->getDefaultVersion();
        if (!$product->hasVersion($version)) {
            return redirect(route($routeNames['product_page'], [$product->key, $defaultVersion]), 301);
        }

        // get page content
        $page = $page ?: 'installation';
        $content = $this->docs->getPage($product, $version, $page);

        // ensure page has content
        if (empty($content)) {
            Log::warning("Documentation page ({$page}) for {$product->getName()} has no content.", ['product' => $product]);
            abort(404);
        }

        $title = (new Crawler($content))->filterXPath('//h1');
        $section = '';

        // ensure section exists
        if ($this->docs->sectionExists($product, $version, $page)) {
            $section .= "/${page}";
        } elseif (!empty($page)) {
            // section does not exist, go to version index
            return redirect()->route($routeNames['product_page'], [$product->key, $version]);
        }

        // set canonical
        $canonical = null;
        if ($this->docs->sectionExists($product, $defaultVersion, $page)) {
            $canonical = route($routeNames['product_page'], [$product->key, $defaultVersion, $page]);
        }

        return view('docweaver::page', [
            'canonical' => $canonical,
            'content' => $content,
            'currentProduct' => $product,
            'currentSection' => $section,
            'currentVersion' => $version,
            'index' => $this->docs->getIndex($product, $version),
            'page' => $page,
            'routeConfig' => $routeConfig,
            'title' => count($title) ? $title->text() : null,
            'versions' => $product->getVersions(),
            'viewTemplateInfo' => $this->viewTemplateInfo,
        ]);
    }
}
