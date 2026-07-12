<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetPhoto;
use App\Models\WorkOrderAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    public function assetPhoto(AssetPhoto $assetPhoto): BinaryFileResponse
    {
        Gate::authorize('view', $assetPhoto->asset);

        return $this->download($assetPhoto->image_path, $assetPhoto->original_name);
    }

    public function workOrder(WorkOrderAttachment $workOrderAttachment): BinaryFileResponse
    {
        Gate::authorize('view', $workOrderAttachment->workOrder);

        return $this->download($workOrderAttachment->file_path, $workOrderAttachment->original_name);
    }

    private function download(string $path, ?string $name): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->download(Storage::disk('local')->path($path), $name ?: basename($path));
    }
}
