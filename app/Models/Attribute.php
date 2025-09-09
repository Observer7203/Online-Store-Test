<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    protected $fillable = ['name', 'code', 'type'];

    public function productAttributes()
{
    return $this->hasMany(ProductAttribute::class);
}


}
