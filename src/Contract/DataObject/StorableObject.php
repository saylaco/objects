<?php

namespace Sayla\Objects\Contract\DataObject;

use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\Storable;
use Sayla\Objects\Contract\Triggerable;

interface StorableObject extends Storable, IDataObject, Triggerable
{
}