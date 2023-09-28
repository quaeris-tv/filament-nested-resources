<?php

declare(strict_types=1);

namespace SevendaysDigital\FilamentNestedResources;

use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

abstract class NestedResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldRegisterNavigationWhenInContext = true;

    protected static string|array $middlewares = [];

    /**
     * @return class-string<resource|NestedResource>
     */
    abstract public static function getParent(): string;

    public static function getParentAccessor(): string
    {
        return Str::of(static::getParent()::getModel())
            ->afterLast('\\Models\\')
            ->camel()
            ->toString();
    }


    public static function getParentId(): int|string|null
    {
        $parentId = Route::current()->parameter(static::getParentAccessor(), Route::current()->parameter('record'));

        return $parentId instanceof Model ? $parentId->getKey() : $parentId;
    }

    public static function getEloquentQuery(string|int $parent = null): Builder
    {
        $query = parent::getEloquentQuery();
        $parentModel = static::getParent()::getModel();
        $key = (new $parentModel())->getKeyName();

        $parentScope = 'of'.Str::studly(Str::afterLast(static::getParent()::getModel(), '\\'));

        if ($query->hasNamedScope($parentScope)) {
            return $query->{$parentScope}($parent ?? static::getParentId());
        }

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
        $parents = static::getParentTree(static::getParent());

        foreach ($parents as $parent) {
            $prefix .= $parent->urlPart.'/{'.$parent->urlPlaceholder.'}/';
        }

        $res = Route::name("$slug.")
            ->prefix($prefix.$slug)
            ->middleware(static::getMiddlewares())
            ->group(function () use ($panel) {
                foreach (static::getPages() as $name => $page) {
                    // Route::get($page['route'], $page['class'])->name($name);
                    $page->registerRoute($panel)?->name($name);
                }
            });
    }

    public static function getMiddlewares(): string|array
    {
        return static::$middlewares;
    }

    // public static function getUrl($name = 'index', $params = [], $isAbsolute = true): string
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, string $panel = null, Model $tenant = null): string
    {
        $params = $parameters;

        $list = static::getParentParametersForUrl(static::getParent(), $params);

        $params = [...$params, ...$list];

        // Attempt to figure out what url binding should be set for the record.
        $childParams = Route::current()->parameters();

        if (isset($childParams['record'])) {
            /** @var Page $controller */
            $controller = Route::current()->getController();
            /** @var resource $resource */
            $resource = $controller::getResource();
            $slug = Str::singular($resource::getSlug());
            $params[$slug] = $childParams['record'];
        }

        $session_key = basename(static::class).'-'.$name.'-params';
        $session_url_params = Session::get($session_key);
        if (! \is_array($session_url_params)) {
            $session_url_params = [];
        }
        // $url_params = [...$session_url_params, ...$childParams, ...$params];
        $url_params = [...$childParams, ...$params];
        foreach ($url_params as $key => $value) {
            if (null === $value && isset($session_url_params[$key])) {
                $url_params[$key] = $session_url_params[$key];
            }
        }

        Session::put($session_key, $url_params);
        try {
            $url = parent::getUrl($name, $url_params, $isAbsolute, $panel, $tenant);
        } catch (\Exception $e) {
            /*
            dd([
                'e' => $e->getMessage(),
                'name' => $name,
                'url_params' => $url_params,
                'session_url_params' => $session_url_params,
                'childParams' => $childParams,
                'params' => $params,
            ]);
            */
            $url = '#';
        }

        return $url;
    }

    /**
     * @param class-string<resource|NestedResource> $parent
     *
     * @return NestedEntry[]
     */
    public static function getParentTree(string $parent, array $urlParams = []): array
    {
        $singularSlug = Str::camel(Str::singular($parent::getSlug()));

        $list = [];
        // if (new $parent() instanceof NestedResource) {
        if (method_exists($parent, 'getParent')) {
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
     * @param class-string<resource|NestedResource> $parent
     */
    public static function getParentParametersForUrl(string $parent, array $urlParameters = []): array
    {
        $list = [];

        $singularSlug = Str::camel(Str::singular($parent::getSlug()));
        // if (new $parent() instanceof NestedResource) {
        if (method_exists($parent, 'getParent')) {
            $list = static::getParentParametersForUrl($parent::getParent(), $urlParameters);
        }
        $list[$singularSlug] = Route::current()?->parameter(
            $singularSlug,
            $urlParameters[$singularSlug] ?? null
        );
        // dddx(['singularSlug'=>$singularSlug,'r'=>Route::current()]);
        foreach ($list as $key => $value) {
            if ($value instanceof Model) {
                $list[$key] = $value->getKey();
            }
        }

        return $list;
    }

    public static function getNavigationGroup(): ?string
    {
        if (static::getParentId()) {// not work with morph
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