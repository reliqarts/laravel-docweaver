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

    public function getMasterTemplate(): string
    {
        return $this->masterTemplate;
    }

    public function getMasterSection(): string
    {
        return $this->masterSection;
    }

    public function getStyleStack(): string
    {
        return $this->styleStack;
    }

    public function hasStyleStack(): bool
    {
        return !empty($this->getStyleStack());
    }

    public function getScriptStack(): string
    {
        return $this->scriptStack;
    }

    public function hasScriptStack(): bool
    {
        return !empty($this->getScriptStack());
    }

    public function getIndexTitle(): string
    {
        return $this->indexTitle;
    }

    public function getIndexIntro(): string
    {
        return $this->indexIntro;
    }

    public function isShowProductLine(): bool
    {
        return $this->showProductLine;
    }

    public function isShowFootnotes(): bool
    {
        return $this->showFootnotes;
    }
}
