<?php

namespace Sayla\Objects\Strategies\Illuminate;

use Illuminate\Database\Eloquent\Model;
use Sayla\Objects\BaseDataModel;

class GenericEloquentStrategy extends EloquentStrategy
{

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function createModel(BaseDataModel $object)
    {
        $model = $this->model->newInstance($object->jsonSerialize());
        $model->save();
        return $model->getAttributes();
    }

    /**
     * @param $model
     */
    protected function deleteModel(Model $model)
    {
        $model->delete();
    }

    /**
     * @param $model
     */
    protected function updateModel(Model $model, BaseDataModel $object)
    {
        $model->save();
    }


}