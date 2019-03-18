<?php

namespace Laralord\Orion\Traits;

use App\Models\Post;
use App\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait HandlesRelationOperations
{
    /**
     * Fetch the list of relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return ResourceCollection
     */
    public function index(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeIndex($request);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        if ($this->authorizationRequired()) $this->authorize('index', static::$model);

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entities = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->paginate();

        $afterHookResult = $this->afterIndex($request, $entities);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return static::$collectionResource ? new static::$collectionResource($entities) : static::$resource::collection($entities);
    }

    /**
     * Create new relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @return Resource
     */
    public function store(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeStore($request);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $relationModelClass = $this->getRelationModelClass();

        if ($this->authorizationRequired()) $this->authorize('store', $relationModelClass);

        /**
         * @var Model $entity
         */
        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);

        $entity = new $relationModelClass();
        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) return $beforeSaveHookResult;

        $resourceEntity->{static::$relation}()->save($entity);

        $entity->load($this->relationsFromIncludes($request));

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) return $afterSaveHookResult;

        $afterHookResult = $this->afterStore($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Fetch a relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int $relationID
     * @return Resource
     */
    public function show(Request $request, $resourceID, $relationID)
    {
        $beforeHookResult = $this->beforeShow($request, $relationID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relationID);

        if ($this->authorizationRequired()) $this->authorize('show', $entity);

        $afterHookResult = $this->afterShow($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Update a relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int $relationID
     * @return Resource
     */
    public function update(Request $request, $resourceID, $relationID)
    {
        $beforeHookResult = $this->beforeUpdate($request, $relationID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relationID);

        if ($this->authorizationRequired()) $this->authorize('update', $entity);

        $entity->fill($request->only($entity->getFillable()));

        $beforeSaveHookResult = $this->beforeSave($request, $entity);
        if ($this->hookResponds($beforeSaveHookResult)) return $beforeSaveHookResult;

        $resourceEntity->{static::$relation}()->save($entity);

        $entity->load($this->relationsFromIncludes($request));

        $afterSaveHookResult = $this->afterSave($request, $entity);
        if ($this->hookResponds($afterSaveHookResult)) return $afterSaveHookResult;

        $afterHookResult = $this->afterUpdate($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Delete a relation resource.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int $relationID
     * @return Resource
     * @throws \Exception
     */
    public function destroy(Request $request, $resourceID, $relationID)
    {
        $beforeHookResult = $this->beforeDestroy($request, $relationID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        $entity = $this->buildRelationMethodQuery($request, $resourceEntity)->with($this->relationsFromIncludes($request))->findOrFail($relationID);

        if ($this->authorizationRequired()) $this->authorize('destroy', $entity);

        $entity->delete();

        $afterHookResult = $this->afterDestroy($request, $entity);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return new static::$resource($entity);
    }

    /**
     * Sync relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function sync(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeSync($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) $this->authorize('update', $resourceEntity);

        $syncResult = $resourceEntity->{static::$relation}()->sync($this->prepareResourcePivotFields($request->get('resources')), $request->get('detaching', true));

        $afterHookResult = $this->afterSync($request, $syncResult);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return response()->json($syncResult);
    }

    /**
     * Toggle relation resources.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function toggle(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeToggle($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) $this->authorize('update', $resourceEntity);

        $togleResult = $resourceEntity->{static::$relation}()->toggle($this->prepareResourcePivotFields($request->get('resources')));

        $afterHookResult = $this->afterToggle($request, $togleResult);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return response()->json($togleResult);
    }

    /**
     * Attach resource to the relation.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function attach(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeAttach($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) $this->authorize('update', $resourceEntity);

        if ($request->get('duplicates'))
            $attachResult = $resourceEntity->{static::$relation}()->attach($this->prepareResourcePivotFields($request->get('resources')));
        else
            $attachResult = $resourceEntity->{static::$relation}()->sync($this->prepareResourcePivotFields($request->get('resources')), false);

        $afterHookResult = $this->afterAttach($request, $attachResult);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return response()->json($attachResult);
    }

    /**
     * Detach resource to the relation.
     *
     * @param Request $request
     * @param int $resourceID
     * @return JsonResponse
     */
    public function detach(Request $request, $resourceID)
    {
        $beforeHookResult = $this->beforeDetach($request, $resourceID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) $this->authorize('update', $resourceEntity);

        $detachResult = $resourceEntity->{static::$relation}()->detach($this->prepareResourcePivotFields($request->get('resources')));

        $afterHookResult = $this->afterDetach($request, $detachResult);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return response()->json($detachResult);
    }

    /**
     * Update relation resource pivot.
     *
     * @param Request $request
     * @param int $resourceID
     * @param int $relationID
     * @return JsonResponse
     */
    public function updatePivot(Request $request, $resourceID, $relationID)
    {
        $beforeHookResult = $this->beforeUpdatePivot($request, $relationID);
        if ($this->hookResponds($beforeHookResult)) return $beforeHookResult;

        $resourceEntity = $this->buildMethodQuery($request)->with($this->relationsFromIncludes($request))->findOrFail($resourceID);
        if ($this->authorizationRequired()) $this->authorize('update', $resourceEntity);

        $updateResult = $resourceEntity->{static::$relation}()->updateExistingPivot($relationID, $this->preparePivotFields($request->get('pivot', [])));

        $afterHookResult = $this->afterUpdatePivot($request, $updateResult);
        if ($this->hookResponds($afterHookResult)) return $afterHookResult;

        return response()->json($updateResult);
    }

    /**
     * Retrieves only fillable pivot fields and json encodes any objects/arrays.
     *
     * @param array $resources
     * @return array
     */
    protected function prepareResourcePivotFields($resources)
    {
        $resources = array_wrap($resources);

        foreach ($resources as $key => &$pivotFields) {
            if (!is_array($pivotFields)) continue;
            $pivotFields = array_only($pivotFields, $this->pivotFillable);
            $pivotFields = $this->preparePivotFields($pivotFields);
        }

        return $resources;
    }

    /**
     * Json encodes any objects/arrays of the given pivot fields.
     *
     * @param array $pivotFields
     * @return array mixed
     */
    protected function preparePivotFields($pivotFields)
    {
        foreach ($pivotFields as &$field) {
            if (is_array($field) || is_object($field)) $field = json_encode($field);
        }

        return $pivotFields;
    }

    /**
     * The hooks is executed before fetching the list of relation resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeIndex(Request $request)
    {
        return null;
    }

    /**
     * The hooks is executed after fetching the list of relation resources.
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
     * The hook is executed before creating new relation resource.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeStore(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after creating new relation resource.
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
     * The hook is executed before fetching relation resource.
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
     * The hook is executed after fetching a relation resource
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
     * The hook is executed before updating a relation resource.
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
     * The hook is executed after updating a relation resource.
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
     * The hook is executed before deleting a relation resource.
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
     * The hook is executed after deleting a relation resource.
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
     * The hook is executed before creating or updating a relation resource.
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
     * The hook is executed after creating or updating a relation resource.
     *
     * @param Request $request
     * @param Model $entity
     * @return mixed
     */
    protected function afterSave(Request $request, $entity)
    {
        return null;
    }

    /**
     * The hook is executed before syncing relation resources.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeSync(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after syncing relation resources.
     *
     * @param Request $request
     * @param array $syncResult
     * @return mixed
     */
    protected function afterSync(Request $request, &$syncResult)
    {
        return null;
    }

    /**
     * The hook is executed before toggling relation resources.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeToggle(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after toggling relation resources.
     *
     * @param Request $request
     * @param array $toggleResult
     * @return mixed
     */
    protected function afterToggle(Request $request, &$toggleResult)
    {
        return null;
    }

    /**
     * The hook is executed before attaching relation resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeAttach(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after attaching relation resource.
     *
     * @param Request $request
     * @param array $toggleResult
     * @return mixed
     */
    protected function afterAttach(Request $request, &$toggleResult)
    {
        return null;
    }

    /**
     * The hook is executed before detaching relation resource.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeDetach(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after detaching relation resource.
     *
     * @param Request $request
     * @param array $toggleResult
     * @return mixed
     */
    protected function afterDetach(Request $request, &$toggleResult)
    {
        return null;
    }

    /**
     * The hook is executed before updating relation resource pivot.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function beforeUpdatePivot(Request $request, $id)
    {
        return null;
    }

    /**
     * The hook is executed after updating relation resource pivot.
     *
     * @param Request $request
     * @param array $updateResult
     * @return mixed
     */
    protected function afterUpdatePivot(Request $request, &$updateResult)
    {
        return null;
    }

    /**
     * Get Eloquent query builder for the relation model and apply filters, searching and sorting.
     *
     * @param Request $request
     * @param Model $resourceEntity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildRelationQuery(Request $request, $resourceEntity)
    {
        /**
         * @var Builder $query
         */
        $query = $resourceEntity->{static::$relation}();

        // only for index method (well, and show method also, but it does not make sense to sort, filter or search data in the show method via query parameters...)
        if ($request->isMethod('GET')) {
            $this->applyFiltersToQuery($request, $query);
            $this->applySearchingToQuery($request, $query);
            $this->applySortingToQuery($request, $query);
        }

        return $query;
    }

    /**
     * Get custom query builder, if any, otherwise use default; apply filters, searching and sorting.
     *
     * @param Request $request
     * @param Model $resourceEntity
     * @return Builder
     */
    protected function buildRelationMethodQuery(Request $request, $resourceEntity)
    {
        $method = debug_backtrace()[1]['function'];
        $customQueryMethod = 'buildRelation' . ucfirst($method) . 'Query';

        if (method_exists($this, $customQueryMethod)) return $this->{$customQueryMethod}($request);
        return $this->buildRelationQuery($request, $resourceEntity);
    }

    /**
     * Get relation model class from the relation.
     *
     * @return string
     */
    protected function getRelationModelClass()
    {
        return get_class(with(new static::$model)->{static::$relation}()->getModel());
    }
}
