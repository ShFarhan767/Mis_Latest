<?php

namespace App\Repositories;

use App\Models\ShopType;

class ShopTypeRepository
{
    public function all()
    {
        return ShopType::orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return ShopType::findOrFail($id);
    }

    public function create(array $data)
    {
        return ShopType::create($data);
    }

    public function update(ShopType $shopType, array $data)
    {
        $shopType->update($data);
        return $shopType;
    }

    public function delete(ShopType $shopType)
    {
        return $shopType->delete();
    }
}
