<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Model;

class TemplateConfig
{
    /**
     * @var string
     */
    private string $masterTemplate;

    /**
     * @var string
     */
    private string $masterSection;

    /**
     * @var string
     */
    private string $styleStack;

    /**
     * @var string
     */
    private string $scriptStack;

    /**
     * @var string
     */
    private string $indexTitle;

    /**
     * @var string
     */
    private string $indexIntro;

    /**
     * @var bool
     */
    private bool $showProductLine;

    /**
     * @var bool
     */
    private bool $showFootnotes;

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
