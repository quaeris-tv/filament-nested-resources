<?php

namespace SevendaysDigital\FilamentNestedResources\Table\Actions;

use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;
use SevendaysDigital\FilamentNestedResources\NestedResource;
use Webmozart\Assert\Assert;

class LinkToChildrenAction extends Action
{
    /** @var class-string<NestedResource> */
    private string $childResource;

    public function forChildResource(string $childResource): self
    {
        Assert::classExists($childResource);
        $this->childResource = $childResource;

        return $this;
    }

    public function getUrl(): ?string
    {

        //$parent = $this->getRecord()->{$this->getRecord()->getKeyName()};
        $parent = $this->getRecord()->getKey();

        $params = [Str::camel(Str::singular($this->childResource::getParent()::getSlug())) => $parent];

        return $this->childResource::getUrl(
            'index',
            $this->childResource::getParentParametersForUrl($this->childResource, $params)
        );
    }
}
