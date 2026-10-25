<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StudioOwner\PackageStoreRequest;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\Admin\CategoriesModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PackagesController extends Controller
{
    use HandlesMediaUploads;

    /**
     * Display a listing of packages.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get packages for the current user
        $packages = PackagesModel::with(['studio', 'category'])
            ->whereHas('studio', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('owner.view-packages', compact('packages'));
    }

    /**
     * Show the form for creating a new package.
     */
    public function create()
    {
        $userId = Auth::id();
        
        // List every owned studio. A studio that cannot take packages yet is
        // shown disabled with the reason, so the owner knows what to fix.
        $accessibleIds = StudiosModel::where('user_id', $userId)->subscriptionAccessible()->pluck('id');
        $studios = StudiosModel::where('user_id', $userId)
            ->orderBy('studio_name')
            ->get()
            ->each(function (StudiosModel $studio) use ($accessibleIds) {
                $studio->package_block_reason = match (true) {
                    $studio->status !== 'verified' => 'awaiting verification',
                    ! $accessibleIds->contains($studio->id) => 'needs an active subscription',
                    default => null,
                };
            });

        // Get all active categories
        $categories = CategoriesModel::where('status', 'active')->get();

        return view('owner.create-packages', compact('studios', 'categories'));
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(PackageStoreRequest $request)
    {
        try {
            // Check if studio belongs to current user
            $studio = StudiosModel::where('id', $request->studio_id)
                ->where('user_id', Auth::id())
                ->where('status', 'verified')
                ->first();

            if (! $studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'This studio is awaiting verification and cannot have packages yet.',
                ], 422);
            }

            // Get validated data from the request
            $validatedData = $request->validated();

            // ==== Start: Handle package_inclusions conversion ====
            // Convert JSON string to array if needed
            if (isset($validatedData['package_inclusions']) && is_string($validatedData['package_inclusions'])) {
                $decoded = json_decode($validatedData['package_inclusions'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $validatedData['package_inclusions'] = $decoded;
                }
            }
            // ==== End: Handle package_inclusions conversion ====

            // ==== Start: Ensure package_location is stored as JSON ====
            // The package_location is already an array from validation
            // Cast will handle JSON conversion automatically
            // ==== End: Ensure package_location is stored as JSON ====

            // Handle cover image uploads (up to 5)
            unset($validatedData['cover_images']);
            if ($request->hasFile('cover_images')) {
                $coverImages = [];
                foreach ($request->file('cover_images') as $image) {
                    $coverImages[] = $image->store('package-covers/' . $studio->id, 'public');
                }
                $validatedData['cover_images'] = $coverImages;
            }

            unset($validatedData['cover_videos']);
            $validatedData['cover_videos'] = $this->syncMedia($request, 'cover_videos', 'keep_cover_videos', [], 'package-videos/' . $studio->id, 5);

            // Create package with all validated data
            $package = PackagesModel::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Package created successfully!',
                'data' => $package
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Package create failed', [
                'studio_id' => $request->studio_id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create package. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing a package.
     */
    public function edit($package)
    {
        $userId = Auth::id();

        $packageModel = PackagesModel::whereHas('studio', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->findOrFail($package);

        $studios = StudiosModel::where('user_id', $userId)
            ->where('status', 'verified')
            ->get();

        $categories = CategoriesModel::where('status', 'active')->get();

        return view('owner.edit-packages', ['package' => $packageModel, 'studios' => $studios, 'categories' => $categories]);
    }

    /**
     * Update the specified package in storage.
     */
    public function update(PackageStoreRequest $request, $package)
    {
        try {
            $userId = Auth::id();

            $packageModel = PackagesModel::whereHas('studio', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->findOrFail($package);

            $studio = StudiosModel::where('id', $request->studio_id)
                ->where('user_id', $userId)
                ->where('status', 'verified')
                ->firstOrFail();

            $validatedData = $request->validated();

            if (isset($validatedData['package_inclusions']) && is_string($validatedData['package_inclusions'])) {
                $decoded = json_decode($validatedData['package_inclusions'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $validatedData['package_inclusions'] = $decoded;
                }
            }

            // Handle cover image uploads: keep existing images the client asked to retain,
            // remove the rest from storage, then append any newly uploaded images.
            unset($validatedData['cover_images']);
            $existingImages = $packageModel->cover_images ?? [];
            $keepImages = $request->input('keep_cover_images', $existingImages);

            foreach ($existingImages as $image) {
                if (!in_array($image, $keepImages)) {
                    Storage::disk('public')->delete($image);
                }
            }

            $coverImages = array_values(array_intersect($existingImages, $keepImages));

            if ($request->hasFile('cover_images')) {
                foreach ($request->file('cover_images') as $image) {
                    if (count($coverImages) >= 5) {
                        break;
                    }
                    $coverImages[] = $image->store('package-covers/' . $studio->id, 'public');
                }
            }

            $validatedData['cover_images'] = $coverImages;

            unset($validatedData['cover_videos']);
            $validatedData['cover_videos'] = $this->syncMedia($request, 'cover_videos', 'keep_cover_videos', $packageModel->cover_videos ?? [], 'package-videos/' . $studio->id, 5);

            $packageModel->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully!',
                'data' => $packageModel
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update package. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified package.
     */
    public function list()
    {
        $userId = Auth::id();
        
        // Get packages for the current user
        $packages = PackagesModel::with(['studio', 'category'])
            ->whereHas('studio', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all active categories for the filter dropdown
        $categories = \App\Models\Admin\CategoriesModel::where('status', 'active')->get();

        return view('owner.packages-list', compact('packages', 'categories'));
    }

    /**
     * Display the specified package.
     */
    public function show($id)
    {
        try {
            $userId = Auth::id();
            
            // Get package with relationships, ensure it belongs to current user
            $package = PackagesModel::with(['studio', 'category'])
                ->whereHas('studio', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $package
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found or you do not have permission to view it.'
            ], 404);
        }
    }
}