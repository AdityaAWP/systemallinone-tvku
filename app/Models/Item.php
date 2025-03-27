<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['name', 'category', 'stock'];
    
    // Add this method to check if stock is available
    public function hasStock($quantity)
    {
        return $this->stock >= $quantity;
    }
    
    // Add this method to decrease stock
    public function decreaseStock($quantity)
    {
        $this->stock -= $quantity;
        $this->save();
    }
    
    // Add this method to increase stock
    public function increaseStock($quantity)
    {
        $this->stock += $quantity;
        $this->save();
    }
}