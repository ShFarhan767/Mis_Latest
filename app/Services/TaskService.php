<?php

namespace App\Services;

use App\Repositories\TaskRepository;
use App\Services\ImageService;

class TaskService
{
    protected $repo;

    public function __construct(TaskRepository $repo)
    {
        $this->repo = $repo;
    }

    public function store(array $data, $uploadedImage = null)
    {
        if ($uploadedImage) {
            $data['image_path'] = ImageService::upload($uploadedImage, 'tasks');
        }

        return $this->repo->create($data);
    }

    public function update(int $id, array $data, $uploadedImage = null)
    {
        $task = $this->repo->find($id);

        if (!$task) {
            throw new \Exception("Task not found");
        }

        if ($uploadedImage) {
            $data['image_path'] = ImageService::upload($uploadedImage, 'tasks', $task->image_path);
        }

        return $this->repo->update($id, $data);
    }

    public function delete(int $id)
    {
        $task = $this->repo->find($id);

        if (!$task) {
            throw new \Exception("Task not found");
        }

        if ($task->image_path) {
            ImageService::delete($task->image_path, 'tasks');
        }

        return $this->repo->delete($id);
    }

    public function all()
    {
        return $this->repo->all();
    }
}

