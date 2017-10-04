<?php

namespace ReliQArts\DocWeaver\Http\Controllers;

use Symfony\Component\DomCrawler\Crawler;
use ReliQArts\DocWeaver\Models\Documentation;
use ReliQArts\DocWeaver\Helpers\CoreHelper as Helper;

class DocsController
{
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
     * @return void
     */
    public function __construct(Documentation $docs)
    {
        $this->docs = $docs;
        $this->docsHome = Helper::getRoutePrefix();
        $this->viewTemplateInfo = Helper::getViewTemplateInfo();
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

        if (!empty($this->viewTemplateInfo['docs_title'])) {
            $title = $this->viewTemplateInfo['docs_title'];
        }

        return view('doc-weaver::index', [
            'title' => $title,
            'products' => $products,
            'viewTemplateInfo' => $this->viewTemplateInfo,
            'routeConfig' => Helper::getRouteConfig()
        ]);
    }

    /**
     * Show the index page for a product (docs/foo).
     *
     * @param string $product
     * @return Response
     */
    public function productIndex($product)
    {
        if (!$this->docs->productExists($product)) {
            abort(404);
        }

        $defaultVersion = $this->docs->getDefaultVersion($product);
        
        return redirect("{$this->docsHome}/$product/$defaultVersion");
    }

    /**
     * Show a documentation page.
     *
     * @param  string $product
     * @param  string $version
     * @param  string|null $page
     * @return Response
     */
    public function show($product, $version, $page = null)
    {       
        if (!$this->docs->productExists($product)) {
            abort(404);
        }
        
        $defaultVersion = $this->docs->getDefaultVersion($product); 
        if (!$this->isVersion($product, $version)) {
            return redirect("{$this->docsHome}/$product/$defaultVersion", 301);
        }

        if (! defined('CURRENT_VERSION')) {
            define('CURRENT_VERSION', $version);
        }

        $sectionPage = $page ?: 'installation';
        $content = $this->docs->get($product, $version, $sectionPage);

        if (is_null($content)) {
            abort(404);
        }

        $title = (new Crawler($content))->filterXPath('//h1');
        $section = '';

        if ($this->docs->sectionExists($product, $version, $page)) {
            $section .= '/'.$page;
        } elseif (! is_null($page)) {
            return redirect("/{$this->docsHome}/$product/$version");
        }

        $canonical = null;

        if ($this->docs->sectionExists($product, $defaultVersion, $sectionPage)) {
            $canonical = "{$this->docsHome}/$product/$defaultVersion/$sectionPage";
        }

        return view('doc-weaver::page', [
            'title' => count($title) ? $title->text() : null,
            'index' => $this->docs->getIndex($product, $version),
            'currentProduct' => $this->docs->getProduct($product),
            'content' => $content,
            'currentVersion' => $version,
            'versions' => $this->docs->getDocVersions($product),
            'currentSection' => $section,
            'canonical' => $canonical,
            'viewTemplateInfo' => $this->viewTemplateInfo,
            'routeConfig' => Helper::getRouteConfig()
        ]);
    }

    /**
     * Determine if the given URL segment is a valid version.
     *
     * @param  string  $product
     * @param  string  $version
     * @return bool
     */
    protected function isVersion($product, $version)
    {
        return in_array($version, array_keys($this->docs->getDocVersions($product)));
    }
}
