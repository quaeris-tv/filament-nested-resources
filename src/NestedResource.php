<?php

namespace SevendaysDigital\FilamentNestedResources;

use Closure;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Exceptions\UrlGenerationException;

abstract class NestedResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldRegisterNavigationWhenInContext = true;

    protected static string | array $middlewares = [];

    /**
     * @return class-string<resource|NestedResource>
     */
    abstract public static function getParent(): string;

    public static function getParentAccessor(): string
    {
        return Str::of(static::getParent()::getModel())
            ->afterLast('\\Models\\')
            ->camel();
    }

    public static function getParentId(): int|string|null
    {
        $parentId = Route::current()->parameter(static::getParentAccessor(), Route::current()->parameter('record'));

        return $parentId instanceof Model ? $parentId->getKey() : $parentId;
    }

    public static function getEloquentQuery(string|int|null $parent = null): Builder
    {
        $query = parent::getEloquentQuery();
        $parentModel = static::getParent()::getModel();
        $key = (new $parentModel())->getKeyName();
        $query->whereHas(
            static::getParentAccessor(),
            fn (Builder $builder) => $builder->where($key, '=', $parent ?? static::getParentId())
        );

        return $query;
    }

    public static function routes(\Filament\Panel $panel): void
    {
         $slug = static::getSlug();

            $prefix = '';
            $parents=static::getParentTree(static::getParent());

            foreach ($parents as $parent) {
                $prefix .= $parent->urlPart.'/{'.$parent->urlPlaceholder.'}/';
            }

            Route::name("$slug.")
                ->prefix($prefix.$slug)
                ->middleware(static::getMiddlewares())
                ->group(function () use ($panel) {
                    foreach (static::getPages() as $name => $page) {
                        //Route::get($page['route'], $page['class'])->name($name);
                        $page->registerRoute($panel)?->name($name);
                    }
                });
    }


    public static function getRoutes(): Closure
    {
        dddx('DEPRECATED ????');
        return function () {
            $slug = static::getSlug();

            $prefix = '';
            $parents=static::getParentTree(static::getParent());
            //dddx(['slug'=>$slug,'parents'=>$parents]);
            foreach ($parents as $parent) {
                $prefix .= $parent->urlPart.'/{'.$parent->urlPlaceholder.'}/';
            }

            Route::name("$slug.")
                ->prefix($prefix.$slug)
                ->middleware(static::getMiddlewares())
                ->group(function () {
                    foreach (static::getPages() as $name => $page) {
                        Route::get($page['route'], $page['class'])->name($name);
                    }
                });
        };
    }

    public static function getMiddlewares(): string | array
    {
        return static::$middlewares;
    }


    /**
     * @param  array<mixed>  $params
     */
    //public static function getUrl($name = 'index', $params = [], $isAbsolute = true): string
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $params=$parameters;
        $list = static::getParentParametersForUrl(static::getParent(), $params);

        $params = [...$params, ...$list];

        // Attempt to figure out what url binding should be set for the record.
        $childParams = Route::current()->parameters();

        if (isset($childParams['record'])) {
            /** @var Page $controller */
            $controller = Route::current()->getController();
            /** @var resource $resource */
            $resource = $controller::getResource();
            $slug=Str::singular($resource::getSlug());
            $params[$slug] = $childParams['record'];
        }



        $session_url_params=Session::get('url_params');
        if(!is_array($session_url_params)){
            $session_url_params=[];
        }
        $url_params=[...$childParams, ...$params, ...$session_url_params];
        Session::put('url_params',$url_params);

        $url=parent::getUrl($name, $url_params, $isAbsolute,$panel,$tenant);

        return $url;
    }

    /**
     * @param class-string<Resource|NestedResource> $parent
     * @return NestedEntry[]
     */
    public static function getParentTree(string $parent, array $urlParams = []): array
    {
        $singularSlug = Str::camel(Str::singular($parent::getSlug()));

        $list = [];
        //if (new $parent() instanceof NestedResource) {
        if(method_exists($parent, 'getParent')) {
            $list = [...$list, ...static::getParentTree($parent::getParent(), $urlParams)];
        }

        $urlParams = static::getParentParametersForUrl($parent, $urlParams);

        $id = Route::current()?->parameter(
            $singularSlug,
            $urlParams[$singularSlug] ?? null
        );

        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $list[$parent::getSlug()] = new NestedEntry(
            urlPlaceholder: Str::camel(Str::singular($parent::getSlug())),
            urlPart: $parent::getSlug(),
            resource: $parent,
            label: $parent::getPluralModelLabel(),
            id: $id,
            urlParams: $urlParams
        );

        return $list;
    }

    /**
     * @param class-string<Resource|NestedResource> $parent
     */
    public static function getParentParametersForUrl(string $parent, array $urlParameters = []): array
    {

        $list = [];

        $singularSlug = Str::camel(Str::singular($parent::getSlug()));
        //if (new $parent() instanceof NestedResource) {
        if(method_exists($parent, 'getParent')) {
            $list = static::getParentParametersForUrl($parent::getParent(), $urlParameters);
        }
        $list[$singularSlug] = Route::current()?->parameter(
            $singularSlug,
            $urlParameters[$singularSlug] ?? null
        );
        //dddx(['singularSlug'=>$singularSlug,'r'=>Route::current()]);
        foreach ($list as $key => $value) {
            if ($value instanceof Model) {
                $list[$key] = $value->getKey();
            }
        }

        return $list;
    }

    public static function getNavigationGroup(): ?string
    {
        if (static::getParentId()) {
            return static::getParent()::getRecordTitle(
                static::getParent()::getModel()::find(
                    static::getParentId()
                )
            );
        }

        return static::getParent()::getModelLabel();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (static::$shouldRegisterNavigationWhenInContext) {
            try {
                static::getUrl('index');

                return true;
            } catch (UrlGenerationException) {
                return false;
            }
        }

        return parent::shouldRegisterNavigation();
    }
}
