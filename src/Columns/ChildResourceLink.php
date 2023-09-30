<?php

<<<<<<< HEAD
declare(strict_types=1);

=======
>>>>>>> 73c8e5b (first)
namespace SevendaysDigital\FilamentNestedResources\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use SevendaysDigital\FilamentNestedResources\NestedResource;

class ChildResourceLink extends TextColumn
{
    /**
     * @var class-string<NestedResource>
     */
    private string $resourceClass;

    /**
<<<<<<< HEAD
     * @param class-string<NestedResource> $name
=======
     * @param  class-string<NestedResource>  $name
>>>>>>> 73c8e5b (first)
     */
    public static function make(string $name): static
    {
        $item = parent::make($name);
        $item->forResource($name);
        $item->label($item->getChildLabelPlural());

        return $item;
    }

    public function getChildLabelPlural(): string
    {
        return Str::title($this->resourceClass::getPluralModelLabel());
    }

    public function getChildLabelSingular(): string
    {
        return Str::title($this->resourceClass::getModelLabel());
    }

    public function forResource(string $resourceClass): static
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    public function getState(): string
    {
        $count = $this->getCount();

<<<<<<< HEAD
        return $count.' '.(1 === $count ? $this->getChildLabelSingular() : $this->getChildLabelPlural());
=======
        return $count.' '.($count === 1 ? $this->getChildLabelSingular() : $this->getChildLabelPlural());
>>>>>>> 73c8e5b (first)
    }

    public function getUrl(): ?string
    {
        $baseParams = [];
        if (property_exists($this->table->getLivewire(), 'urlParameters')) {
            $baseParams = $this->table->getLivewire()->urlParameters;
        }

        $param = Str::camel(Str::singular($this->resourceClass::getParent()::getSlug()));
<<<<<<< HEAD

        $params = $baseParams;
        $params[$param] = $this->record->getKey();
        $url = $this->resourceClass::getUrl('index', $params);

        return $url;
=======
        /*
        dddx([
            '$this->resourceClass'=>$this->resourceClass,  //SurveyPdfResource
            '$baseParams'=>$baseParams, // []
            'param'=>$param, // customer
            'geyKey'=>$this->record->getKey(), //1
        ]);
        */
        return $this->resourceClass::getUrl(
            'index',
            [...$baseParams, $param => $this->record->getKey()]
        );
>>>>>>> 73c8e5b (first)
    }

    private function getCount(): int
    {
        return $this->resourceClass::getEloquentQuery($this->record->getKey())->count();
    }
}
