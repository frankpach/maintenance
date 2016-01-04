<?php

namespace App\Repositories\Asset;

use App\Http\Requests\Asset\Request;
use App\Models\Asset;
use App\Repositories\Repository as BaseRepository;
use App\Services\SentryService;

class Repository extends BaseRepository
{
    /**
     * @var SentryService
     */
    protected $sentry;

    /**
     * Constructor.
     *
     * @param SentryService $sentry
     */
    public function __construct(SentryService $sentry)
    {
        $this->sentry = $sentry;
    }

    /**
     * @return Asset
     */
    public function model()
    {
        return new Asset();
    }

    /**
     * Finds an Asset.
     *
     * @param int|string $id
     *
     * @return null|Asset
     */
    public function find($id)
    {
        $with = [
            'location',
            'category',
            'workOrders',
            'images',
            'meters',
            'revisions',
        ];

        return $this->model()->with($with)->findOrFail($id);
    }

    /**
     * Returns a new grid instance of the current model.
     *
     * @param int|string $id
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridEvents($id, array $columns = [], array $settings = [], $transformer = null)
    {
        $model = $this->find($id);

        return $this->newGrid($model->events()->whereNull('parent_id'), $columns, $settings, $transformer);
    }

    /**
     * Returns a new grid instance of all asset work orders.
     *
     * @param int|string $assetId
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridWorkOrders($assetId, array $columns = [], array $settings = [], $transformer = null)
    {
        $model = $this->find($assetId);

        return $this->newGrid($model->workOrders(), $columns, $settings, $transformer);
    }

    /**
     * Returns a new grid instance of all attachable work orders.
     *
     * @param int|string $assetId
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridAttachableWorkOrders($assetId, array $columns = [], array $settings = [], $transformer = null)
    {
        $asset = $this->find($assetId);

        $model = $asset->workOrders()->getRelated()->newInstance()->query()->whereDoesntHave('assets', function ($query) use ($assetId) {
            $query->where('assets.id', '=', $assetId);
        });

        return $this->newGrid($model, $columns, $settings, $transformer);
    }

    /**
     * Returns a new grid instance of all asset images.
     *
     * @param int|string $assetId
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridImages($assetId, array $columns = [], array $settings = [], $transformer = null)
    {
        $model = $this->find($assetId);

        return $this->newGrid($model->images(), $columns, $settings, $transformer);
    }

    /**
     * Returns a new grid instance of all asset manuals.
     *
     * @param int|string $assetId
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridManuals($assetId, array $columns = [], array $settings = [], $transformer = null)
    {
        $model = $this->find($assetId);

        return $this->newGrid($model->manuals(), $columns, $settings, $transformer);
    }

    /**
     * Returns a new grid instance of all asset meters.
     *
     * @param int|string $assetId
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridMeters($assetId, array $columns = [], array $settings = [], $transformer = null)
    {
        $model = $this->find($assetId);

        return $this->newGrid($model->meters(), $columns, $settings, $transformer);
    }

    /**
     * Returns a new grid instance of all readings attached to the asset meter.
     *
     * @param int|string $assetId
     * @param int|string $meterId
     * @param array      $columns
     * @param array      $settings
     * @param \Closure   $transformer
     *
     * @return \Cartalyst\DataGrid\DataGrid
     */
    public function gridMeterReadings($assetId, $meterId, array $columns = [], array $settings = [], $transformer = null)
    {
        $model = $this->find($assetId)->meters()->find($meterId);

        return $this->newGrid($model->readings(), $columns, $settings, $transformer);
    }

    /**
     * Creates a new Asset.
     *
     * @param Request $request
     *
     * @return bool|Asset
     */
    public function create(Request $request)
    {
        $asset = $this->model();

        $asset->user_id = $this->sentry->getCurrentUserId();
        $asset->location_id = $request->input('location_id');
        $asset->category_id = $request->input('category_id');
        $asset->tag = $request->input('tag');
        $asset->name = $request->input('name');
        $asset->description = $request->clean($request->input('description'));
        $asset->condition = $request->input('condition');
        $asset->size = $request->input('size');
        $asset->weight = $request->input('weight');
        $asset->vendor = $request->input('vendor');
        $asset->make = $request->input('make');
        $asset->model = $request->input('model');
        $asset->serial = $request->input('serial');
        $asset->price = $request->input('price');

        if ($request->input('acquired_at')) {
            $asset->acquired_at = $this->strToDate($request->input('acquired_at'));
        }

        if ($request->input('end_of_life')) {
            $asset->end_of_life = $this->strToDate($request->input('end_of_life'));
        }

        if ($asset->save()) {
            return $asset;
        }

        return false;
    }

    /**
     * Updates the specified Asset.
     *
     * @param Request    $request
     * @param int|string $id
     *
     * @return bool|Asset
     */
    public function update(Request $request, $id)
    {
        $asset = $this->model()->findOrFail($id);

        if ($asset) {
            $asset->location_id = $request->input('location_id', $asset->location_id);
            $asset->category_id = $request->input('category_id', $asset->category_id);
            $asset->tag = $request->input('tag', $asset->tag);
            $asset->name = $request->input('name', $asset->name);
            $asset->description = $request->clean($request->input('description', $asset->name));
            $asset->condition = $request->input('condition', $asset->condition);
            $asset->size = $request->input('size', $asset->size);
            $asset->weight = $request->input('weight', $asset->weight);
            $asset->vendor = $request->input('vendor', $asset->vendor);
            $asset->make = $request->input('make', $asset->make);
            $asset->model = $request->input('model', $asset->model);
            $asset->serial = $request->input('serial', $asset->serial);
            $asset->price = $request->input('price', $asset->price);

            if ($request->input('acquired_at')) {
                $asset->acquired_at = $this->strToDate($request->input('acquired_at', $asset->acquired_at));
            }

            if ($request->input('end_of_life')) {
                $asset->end_of_life = $this->strToDate($request->input('end_of_life', $asset->end_of_life));
            }

            if ($asset->save()) {
                return $asset;
            }
        }

        return false;
    }

    /**
     * Attaches the specified work order to the specified asset.
     *
     * @param int|string $id
     * @param int|string $workOrderId
     *
     * @return bool|Asset
     */
    public function attachWorkOrder($id, $workOrderId)
    {
        $asset = $this->model()->findOrFail($id);

        if ($asset) {
            $workOrder = $asset->workOrders()->find($workOrderId);

            // Only attach the work order if it hasn't already been attached.
            if (!$workOrder) {
                $workOrder = $asset->workOrders()->getRelated()->findOrFail($workOrderId);

                $asset->workOrders()->attach($workOrder->id);

                return $asset;
            }
        }

        return false;
    }

    /**
     * Detaches the specified work order from the specified asset.
     *
     * @param int|string $id
     * @param int|string $workOrderId
     *
     * @return bool|Asset
     */
    public function detachWorkOrder($id, $workOrderId)
    {
        $asset = $this->model()->findOrFail($id);

        $workOrder = $asset->workOrders()->findOrFail($workOrderId);

        if ($asset->workOrders()->detach($workOrder->id)) {
            return $asset;
        }

        return false;
    }
}