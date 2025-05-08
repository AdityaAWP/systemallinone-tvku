<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['name', 'category', 'stock'];
    
    public function hasStock($quantity)
    {
        return $this->stock >= $quantity;
    }
    
    public function decreaseStock($quantity)
    {
        $this->stock -= $quantity;
        $this->save();
    }
    
    public function increaseStock($quantity)
    {
        $this->stock += $quantity;
        $this->save();
    }
}