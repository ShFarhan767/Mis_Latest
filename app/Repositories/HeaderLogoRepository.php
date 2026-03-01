<?php

namespace App\Repositories;

use App\Models\HeaderLogo;

class HeaderLogoRepository
{
    public function all()
    {
        return HeaderLogo::latest()->get();
    }

    public function create(array $data)
    {
        return HeaderLogo::create($data);
    }

    public function update(HeaderLogo $logo, array $data)
    {
        $logo->update($data);
        return $logo;
    }

    public function delete(HeaderLogo $logo)
    {
        return $logo->delete();
    }
}
