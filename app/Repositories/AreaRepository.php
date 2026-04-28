<?php

namespace App\Repositories;

use App\Models\Area;

class AreaRepository
{
    public function all()
    {
        // admin see all - select only needed columns
        return Area::select('id', 'area_name', 'country_name', 'status')
            ->orderBy('id', 'DESC')
            ->get();
    }

    public function find($id)
    {
        return Area::findOrFail($id);
    }

    public function store($data, $userId)
    {
        return Area::create([
            'area_name'   => $data['area_name'],
            'status'      => $data['status'],
            'country_name'=> $data['country_name'],
            'created_by'  => $userId, // ✅ now defined
        ]);
    }

    public function update($id, $data)
    {
        $area = Area::findOrFail($id);

        // staff can only edit their own
        if (auth()->user()->role === 'staff' && $area->created_by !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $area->update([
            'area_name' => $data['area_name'],
            'status' => $data['status'],
            'country_name' => $data['country_name'],
        ]);

        return $area;
    }

    public function delete($id)
    {
        Area::destroy($id);
    }
}
