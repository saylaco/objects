<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\Contract\Stores\ObjectStore;

interface Storable
{
    const ON_AFTER_CREATE = 'afterCreate';
    const ON_AFTER_DELETE = 'afterDelete';
    const ON_AFTER_SAVE = 'afterSave';
    const ON_AFTER_UPDATE = 'afterUpdate';
    const ON_BEFORE_CREATE = 'beforeCreate';
    const ON_BEFORE_DELETE = 'beforeDelete';
    const ON_BEFORE_SAVE = 'beforeSave';
    const ON_BEFORE_UPDATE = 'beforeUpdate';

    /**
     * @return ObjectStore
     */
    public static function getStore();

    public function create();

    public function delete();

    public function exists();

    public function update();
}