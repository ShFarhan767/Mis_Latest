<?php

namespace App\Repositories;

use App\Models\Area;

class AreaRepository
{
    public function all()
    {
        return Area::orderBy('id', 'DESC')->get();
    }

    public function find($id)
    {
        return Area::findOrFail($id);
    }

    public function store($data)
    {
        return Area::create([
            'area_name' => $data['area_name'],
            'status' => $data['status'],
            'country_name' => $data['country_name'], // <-- required link to country
        ]);
    }

    public function update($id, $data)
    {
        $area = Area::findOrFail($id);
        $area->update([
            'area_name' => $data['area_name'],
            'status' => $data['status'],
            'country_name' => $data['country_name'], // <-- update country link
        ]);
        return $area;
    }

    public function delete($id)
    {
        Area::destroy($id);
    }
}
