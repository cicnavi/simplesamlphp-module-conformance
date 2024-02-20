<?php

namespace SimpleSAML\Module\conformance\Menu;

class Item
{
    public function __construct(
        protected string $hrefPath,
        protected string $label,
        protected ?string $iconAssetPath = null
    ) {
    }

    public function getHrefPath(): string
    {
        return $this->hrefPath;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIconAssetPath(): ?string
    {
        return $this->iconAssetPath;
    }
}
