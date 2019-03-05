<?php

namespace Laralord\Orion\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait HandlesCRUDOperations
{
    use BuildsQuery;

    /**
     * Fetch the list of resources.
     *
     * @param Request $request
     * @return ResourceCollection
     */
    public function index(Request $request)
    {
        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        if ($this->authorizationRequired()) $this->authorize('index', static::$model);

        $entities = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->paginate();

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return static::$collectionResource ? new static::$collectionResource($entities) : static::$resource::collection($entities);
    }

    /**
     * Create new resource.
     *
     * @param Request $request
     * @return Resource
     */
    public function store(Request $request)
    {
        $beforeHookResult = $this->beforeStore($request);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        if ($this->authorizationRequired()) $this->authorize('store', static::$model);

        /**
         * @var Model $entity
         */
        $entity = new static::$model;
        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) return $beforeSaveHookResult;

        $entity->save();

        $entity->load($this->relationsFromIncludes($request));

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) return $afterSaveHookResult;

        $afterHookResult = $this->afterStore($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Fetch resource.
     *
     * @param Request $request
     * @param int $id
     * @return Resource
     */
    public function show(Request $request, $id)
    {
        $beforeHookResult = $this->beforeShow($request, $id);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $entity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($id);
        if ($this->authorizationRequired()) $this->authorize('show', $entity);

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Update a resource.
     *
     * @param Request $request
     * @param int $id
     * @return Resource
     */
    public function update(Request $request, $id)
    {
        $beforeHookResult = $this->beforeUpdate($request, $id);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $entity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($id);
        if ($this->authorizationRequired()) $this->authorize('update', $entity);

        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) return $beforeSaveHookResult;

        $entity->save();

        $entity->load($this->relationsFromIncludes($request));

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) return $afterSaveHookResult;

        $afterHookResult = $this->afterUpdate($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Delete a resource.
     *
     * @param Request $request
     * @param int $id
     * @return Resource
     * @throws \Exception
     */
    public function destroy(Request $request, $id)
    {
        $beforeHookResult = $this->beforeDestroy($request, $id);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $entity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($id);
        if ($this->authorizationRequired()) $this->authorize('destroy', $entity);

        $entity->delete();

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * The hooks is executed before fetching the list of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeIndex(Request $request)
    {
        return null;
    }

    /**
     * The hooks is executed after fetching the list of resources.
     *
     * @param Request $request
     * @param LengthAwarePaginator $entities
     * @return mixed
     */
    protected function afterIndex(Request $request, LengthAwarePaginator $entities)
    {
        return null;
    }

    /**
     * The hook is executed before creating new resource.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeStore(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after creating new resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterStore(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before fetching a resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeShow(Request $request, int $id)
    {
        return null;
    }

    /**
     * The hook is executed after fetching a resource
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterShow(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before updating a resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeUpdate(Request $request, int $id)
    {
        return null;
    }

    /**
     * The hook is executed after updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterUpdate(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before deleting a resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeDestroy(Request $request, int $id)
    {
        return null;
    }

    /**
     * The hook is executed after deleting a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterDestroy(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before creating or updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function beforeSave(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed after creating or updating a resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterSave(Request $request, $entity)
    {
        return null;
    }
}
