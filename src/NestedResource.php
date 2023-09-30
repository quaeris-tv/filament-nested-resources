<?php

<<<<<<< HEAD
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
=======
namespace SevendaysDigital\FilamentNestedResources;

use Closure;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Exceptions\UrlGenerationException;
>>>>>>> 73c8e5b (first)

abstract class NestedResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldRegisterNavigationWhenInContext = true;

<<<<<<< HEAD
    protected static string|array $middlewares = [];
=======
    protected static string | array $middlewares = [];
>>>>>>> 73c8e5b (first)

    /**
     * @return class-string<resource|NestedResource>
     */
    abstract public static function getParent(): string;

    public static function getParentAccessor(): string
    {
        return Str::of(static::getParent()::getModel())
            ->afterLast('\\Models\\')
<<<<<<< HEAD
            ->camel()
            ->toString();
    }


=======
            ->camel();
    }

>>>>>>> 73c8e5b (first)
    public static function getParentId(): int|string|null
    {
        $parentId = Route::current()->parameter(static::getParentAccessor(), Route::current()->parameter('record'));

        return $parentId instanceof Model ? $parentId->getKey() : $parentId;
    }

<<<<<<< HEAD
    public static function getEloquentQuery(string|int $parent = null): Builder
=======
    public static function getEloquentQuery(string|int|null $parent = null): Builder
>>>>>>> 73c8e5b (first)
    {
        $query = parent::getEloquentQuery();
        $parentModel = static::getParent()::getModel();
        $key = (new $parentModel())->getKeyName();
<<<<<<< HEAD

        $parentScope = 'of'.Str::studly(Str::afterLast(static::getParent()::getModel(), '\\'));

        if ($query->hasNamedScope($parentScope)) {
            return $query->{$parentScope}($parent ?? static::getParentId());
        }

=======
>>>>>>> 73c8e5b (first)
        $query->whereHas(
            static::getParentAccessor(),
            fn (Builder $builder) => $builder->where($key, '=', $parent ?? static::getParentId())
        );

        return $query;
    }

    public static function routes(\Filament\Panel $panel): void
    {
<<<<<<< HEAD
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
=======
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
>>>>>>> 73c8e5b (first)
    {
        return static::$middlewares;
    }

<<<<<<< HEAD
    // public static function getUrl($name = 'index', $params = [], $isAbsolute = true): string
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, string $panel = null, Model $tenant = null): string
    {
        $params = $parameters;
=======

    /**
     * @param  array<mixed>  $params
     */
    //public static function getUrl($name = 'index', $params = [], $isAbsolute = true): string
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $params=$parameters;
        if (! is_array($params)) {
            $params = [$params];
        }
>>>>>>> 73c8e5b (first)

        $list = static::getParentParametersForUrl(static::getParent(), $params);

        $params = [...$params, ...$list];

        // Attempt to figure out what url binding should be set for the record.
        $childParams = Route::current()->parameters();

        if (isset($childParams['record'])) {
            /** @var Page $controller */
            $controller = Route::current()->getController();
            /** @var resource $resource */
            $resource = $controller::getResource();
<<<<<<< HEAD
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

=======

            $params[Str::singular($resource::getSlug())] = $childParams['record'];
        }
        $url=parent::getUrl($name, [...$childParams, ...$params], $isAbsolute,$panel,$tenant);
        //dddx(['name'=>$name,'$childParams'=>$childParams,'params'=>$params,'isAbsolute','panel'=>$panel,'tenant'=>$tenant,'url'=>$url]);
>>>>>>> 73c8e5b (first)
        return $url;
    }

    /**
<<<<<<< HEAD
     * @param class-string<resource|NestedResource> $parent
     *
=======
     * @param class-string<Resource|NestedResource> $parent
>>>>>>> 73c8e5b (first)
     * @return NestedEntry[]
     */
    public static function getParentTree(string $parent, array $urlParams = []): array
    {
        $singularSlug = Str::camel(Str::singular($parent::getSlug()));

        $list = [];
<<<<<<< HEAD
        // if (new $parent() instanceof NestedResource) {
        if (method_exists($parent, 'getParent')) {
=======
        //if (new $parent() instanceof NestedResource) {
        if(method_exists($parent, 'getParent')) {
>>>>>>> 73c8e5b (first)
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
<<<<<<< HEAD
     * @param class-string<resource|NestedResource> $parent
     */
    public static function getParentParametersForUrl(string $parent, array $urlParameters = []): array
    {
        $list = [];

        $singularSlug = Str::camel(Str::singular($parent::getSlug()));
        // if (new $parent() instanceof NestedResource) {
        if (method_exists($parent, 'getParent')) {
=======
     * @param class-string<Resource|NestedResource> $parent
     */
    public static function getParentParametersForUrl(string $parent, array $urlParameters = []): array
    {

        $list = [];

        $singularSlug = Str::camel(Str::singular($parent::getSlug()));
        //if (new $parent() instanceof NestedResource) {
        if(method_exists($parent, 'getParent')) {
>>>>>>> 73c8e5b (first)
            $list = static::getParentParametersForUrl($parent::getParent(), $urlParameters);
        }
        $list[$singularSlug] = Route::current()?->parameter(
            $singularSlug,
            $urlParameters[$singularSlug] ?? null
        );
<<<<<<< HEAD
        // dddx(['singularSlug'=>$singularSlug,'r'=>Route::current()]);
=======

>>>>>>> 73c8e5b (first)
        foreach ($list as $key => $value) {
            if ($value instanceof Model) {
                $list[$key] = $value->getKey();
            }
        }

        return $list;
    }

    public static function getNavigationGroup(): ?string
    {
<<<<<<< HEAD
        if (static::getParentId()) {// not work with morph
=======
        if (static::getParentId()) {
>>>>>>> 73c8e5b (first)
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
<<<<<<< HEAD
}
=======
}
>>>>>>> 73c8e5b (first)
