<?php

namespace ReliQArts\Docweaver\Http\Controllers;

use Symfony\Component\DomCrawler\Crawler;
use ReliQArts\Docweaver\Models\Documentation;
use ReliQArts\Docweaver\Helpers\CoreHelper as Helper;

class DocsController
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
    public function productIndex($product)
    {
        $routeNames = $this->config['route']['names'];
        $defaultVersion = $this->docs->getDefaultVersion($product);
        
        if (!$this->docs->productExists($product) || $defaultVersion == Documentation::UNKNOWN_VERSION) {
            abort(404);
        }

        return redirect()->route($routeNames['product_page'], [$product, $defaultVersion]);
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
    public function show($product, $version, $page = null)
    {
        $routeConfig = $this->config['route'];
        $routeNames = $routeConfig['names'];
        
        // ensure product exists
        if (! $this->docs->productExists($product)) {
            abort(404);
        }
        
        // get default version for product
        $defaultVersion = $this->docs->getDefaultVersion($product);
        if (!$this->isVersion($product, $version)) {
            return redirect(route($routeNames['product_page'], [$product, $defaultVersion]), 301);
        }

        // get page content
        $sectionPage = $page ?: 'installation';
        $content = $this->docs->get($product, $version, $sectionPage);

        // ensure page has content
        if (is_null($content)) {
            abort(404);
        }

        $title = (new Crawler($content))->filterXPath('//h1');
        $section = '';

        if ($this->docs->sectionExists($product, $version, $page)) {
            $section .= '/'.$page;
        } elseif (!is_null($page)) {
            return redirect()->route($routeNames['product_page'], [$product, $version]);
        }

        $canonical = null;
        if ($this->docs->sectionExists($product, $defaultVersion, $sectionPage)) {
            $canonical = route($routeNames['product_page'], [$product, $defaultVersion, $sectionPage]);
        }

        return view('docweaver::page', [
            'title' => count($title) ? $title->text() : null,
            'index' => $this->docs->getIndex($product, $version),
            'currentProduct' => $this->docs->getProduct($product),
            'content' => $content,
            'currentVersion' => $version,
            'versions' => $this->docs->getDocVersions($product),
            'currentSection' => $section,
            'canonical' => $canonical,
            'viewTemplateInfo' => $this->viewTemplateInfo,
            'routeConfig' => $routeConfig,
        ]);
    }

    /**
     * Determine if the given URL segment is a valid version.
     *
     * @param  string  $product
     * @param  string  $version
     *
     * @return bool
     */
    protected function isVersion($product, $version)
    {
        return in_array($version, array_keys($this->docs->getDocVersions($product)));
    }
}
