<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLogoRequest;
use App\Models\Logo;
use App\Repositories\LogoRepository;
use App\Services\ImageService;
use App\Services\LogoService;
use Illuminate\Http\Request;

class LogoController extends Controller
{
    public function __construct(private LogoService $service, private LogoRepository $repo){}

    public function index()
    {
        return $this->repo->all();
    }

    public function store(StoreLogoRequest $request)
    {
        return $this->service->store($request);
    }

    public function update(StoreLogoRequest $request, Logo $logo)
    {
        return $this->service->update($request,$logo);
    }

    public function destroy(Logo $logo)
    {
        ImageService::delete($logo->logo_path,'logos');
        ImageService::delete($logo->favicon_path,'favicons');

        $this->repo->delete($logo);
        return response()->noContent();
    }
}
