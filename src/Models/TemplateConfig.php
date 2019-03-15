<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Models;

class TemplateConfig
{
    /**
     * @var string
     */
    private $masterTemplate;

    /**
     * @var string
     */
    private $masterSection;

    /**
     * @var string
     */
    private $styleStack;

    /**
     * @var string
     */
    private $scriptStack;

    /**
     * @var string
     */
    private $indexTitle;

    /**
     * @var string
     */
    private $indexIntro;

    /**
     * @var bool
     */
    private $showProductLine;

    /**
     * @var bool
     */
    private $showFootnotes;

    /**
     * TemplateConfig constructor.
     *
     * @param string $masterTemplate
     * @param string $masterSection
     * @param string $styleStack
     * @param string $scriptStack
     * @param string $indexTitle
     * @param string $indexIntro
     * @param bool   $showProductLine
     * @param bool   $showFootnotes
     */
    public function __construct(
        string $masterTemplate,
        string $masterSection,
        string $styleStack,
        string $scriptStack,
        string $indexTitle,
        string $indexIntro,
        bool $showProductLine = true,
        bool $showFootnotes = true
    ) {
        $this->masterTemplate = $masterTemplate;
        $this->masterSection = $masterSection;
        $this->styleStack = $styleStack;
        $this->scriptStack = $scriptStack;
        $this->indexTitle = $indexTitle;
        $this->indexIntro = $indexIntro;
        $this->showProductLine = $showProductLine;
        $this->showFootnotes = $showFootnotes;
    }

    /**
     * @return string
     */
    public function getMasterTemplate(): string
    {
        return $this->masterTemplate;
    }

    /**
     * @return string
     */
    public function getMasterSection(): string
    {
        return $this->masterSection;
    }

    /**
     * @return string
     */
    public function getStyleStack(): string
    {
        return $this->styleStack;
    }

    /**
     * @return bool
     */
    public function hasStyleStack(): bool
    {
        return !empty($this->getStyleStack());
    }

    /**
     * @return string
     */
    public function getScriptStack(): string
    {
        return $this->scriptStack;
    }

    /**
     * @return bool
     */
    public function hasScriptStack(): bool
    {
        return !empty($this->getScriptStack());
    }

    /**
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->indexTitle;
    }

    /**
     * @return string
     */
    public function getIndexIntro(): string
    {
        return $this->indexIntro;
    }

    /**
     * @return bool
     */
    public function isShowProductLine(): bool
    {
        return $this->showProductLine;
    }

    /**
     * @return bool
     */
    public function isShowFootnotes(): bool
    {
        return $this->showFootnotes;
    }
}
