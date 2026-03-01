<?php

namespace App\Services;

use App\Models\HeaderLogo;
use App\Repositories\HeaderLogoRepository;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;

class HeaderLogoService
{
    public function __construct(
        protected HeaderLogoRepository $repository,
        protected ImageService $imageService
    ) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    public function create(UploadedFile $file)
    {
        $path = $this->imageService->upload($file, 'header-logos');
        return $this->repository->create(['image' => $path]);
    }

    public function update(HeaderLogo $logo, ?UploadedFile $file = null)
    {
        $path = $logo->image;
        if ($file) {
            $path = $this->imageService->upload($file, 'header-logos', $path);
        }
        return $this->repository->update($logo, ['image' => $path]);
    }

    public function delete(HeaderLogo $logo)
    {
        $this->imageService->delete($logo->image, 'header-logos');
        return $this->repository->delete($logo);
    }
}
