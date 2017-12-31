<?php

namespace ReliQArts\Docweaver\Http\Controllers;

use Log;
use ReliQArts\Docweaver\Models\Product;
use Symfony\Component\DomCrawler\Crawler;
use ReliQArts\Docweaver\Models\Documentation;
use ReliQArts\Docweaver\Helpers\CoreHelper as Helper;

class DocumentationController
{
    /**
     * Documentation configuration array.
     *
     * @var Cache
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
     * @param  Documentation  $docs
     *
     * @return void
     */
    public function __construct(Documentation $docs)
    {
        $this->docs = $docs;
        $this->docsHome = Helper::getRoutePrefix();
        $this->viewTemplateInfo = Helper::getViewTemplateInfo();
        $this->config = Helper::getConfig();
    }

    /**
     * Show the documentation home page (docs).
     *
     * @return Response
     */
    public function index()
    {
        $title = 'Documentation';
        $products = $this->docs->listProducts();

        if (! empty($this->viewTemplateInfo['docs_title'])) {
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
     * @param string $product
     *
     * @return Response
     */
    public function productIndex($productName)
    {
        $routeNames = $this->config['route']['names'];
        $product = $this->docs->getProduct($productName);
        
        if (!$product || $product->getDefaultVersion() === Product::UNKNOWN_VERSION) {
            abort(404);
        }

        // route to default version
        return redirect()->route($routeNames['product_page'], [
            $product->key,
            $product->getDefaultVersion()
        ]);
    }

    /**
     * Show a documentation page.
     *
     * @param  string $product
     * @param  string $version
     * @param  string|null $page
     *
     * @return Response
     */
    public function show($productKey, $version, $page = null)
    {
        $routeConfig = $this->config['route'];
        $routeNames = $routeConfig['names'];
        
        // ensure product exists
        if (!$product = $this->docs->getProduct($productKey)) {
            abort(404);
        }
        
        // get default version for product
        $defaultVersion = $product->getDefaultVersion();
        if (!$product->hasVersion($version)) {
            return redirect(route($routeNames['product_page'], [$product->key, $defaultVersion]), 301);
        }

        // get page content
        $sectionPage = $page ?: 'installation';
        $content = $this->docs->getPage($product, $version, $sectionPage);

        // ensure page has content
        if (is_null($content)) {
            Log::warning("Documentation page ({$page}) for {$product->getName()} has no content.", [
                'product' => $product]
            );
            abort(404);
        }

        $title = (new Crawler($content))->filterXPath('//h1');
        $section = '';

        // ensure section exists
        if ($this->docs->sectionExists($product, $version, $page)) {
            $section .= "/$page";
        } elseif (!is_null($page)) {
            // section does not exist, go to version index
            return redirect()->route($routeNames['product_page'], [$product->key, $version]);
        }

        // set canonical
        $canonical = null;
        if ($this->docs->sectionExists($product, $defaultVersion, $sectionPage)) {
            $canonical = route($routeNames['product_page'], [$product->key, $defaultVersion, $sectionPage]);
        }

        // dd($version);

        return view('docweaver::page', [
            'title' => count($title) ? $title->text() : null,
            'index' => $this->docs->getIndex($product, $version),
            'currentProduct' => $product,
            'content' => $content,
            'currentVersion' => $version,
            'versions' => $product->getVersions(),
            'currentSection' => $section,
            'canonical' => $canonical,
            'viewTemplateInfo' => $this->viewTemplateInfo,
            'routeConfig' => $routeConfig,
        ]);
    }
}
