<?php
namespace Modules\Catalog\Models;
use Illuminate\Database\Eloquent\Relations\Pivot;
class CollectionProduct extends Pivot { protected $table = 'collection_product'; public $incrementing = false; }
