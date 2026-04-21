<?php

namespace App\Services;

use App\Repositories\LogoRepository;
use App\Models\Logo;              // ✅ THIS IS THE FIX

class LogoService
{
    protected $repo;

    public function __construct(LogoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function store($request)
    {
        $logoPath = ImageService::upload($request->file('logo'), 'logos');
        $faviconPath = ImageService::upload($request->file('favicon'), 'favicons');

        return $this->repo->store([
            'title'        => $request->title,
            'logo_path'    => $logoPath,
            'favicon_path' => $faviconPath,
        ]);
    }

    public function update($request, Logo $logo)
    {
        $logoPath = ImageService::upload(
            $request->file('logo'),
            'logos',
            $logo->logo_path
        );

        $faviconPath = ImageService::upload(
            $request->file('favicon'),
            'favicons',
            $logo->favicon_path
        );

        return $this->repo->update($logo,[
            'title'        => $request->title,
            'logo_path'    => $logoPath,
            'favicon_path' => $faviconPath,
        ]);
    }
}
