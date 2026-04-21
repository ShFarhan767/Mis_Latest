<?php

namespace App\Repositories;

use App\Models\Logo;

class LogoRepository
{
public function all()
    {
        return Logo::latest()->get();
    }

    public function store(array $data)
    {
        return Logo::create($data);
    }

    public function update(Logo $logo, array $data)
    {
        return $logo->update($data);
    }

    public function delete(Logo $logo)
    {
        return $logo->delete();
    }
}
