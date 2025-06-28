<?php
namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\HasJsonResponse;
use App\Support\HttpConstants as HTTP;

class StoreService
{
    use HasJsonResponse;
    public function getAllStores()
{
    $user = Auth::user();

    // Admins can see all stores
    if ($user->role && $user->role->name === 'admin') {
        return Store::with('user')->paginate(20);
    }

    // Non-admins see only their own stores
    return Store::where('user_id', $user->id)->with('user')->get();
}

public function getStoreById($id)
{
    $user = Auth::user();

    $query = Store::where('id', $id)->with('user');

    if ($user->role && $user->role->name !== 'admin') {
        $query->where('user_id', $user->id);
    }

    $store = $query->first();

    if (!$store) {
        // Use your jsonResponse helper or throw custom error
        return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Store not found', $store); // Or:
    }

    return $store;
}

public function updateStore($id, array $data)
{
    $user = Auth::user();

    $query = Store::where('id', $id);

    if ($user->role && $user->role->name !== 'admin') {
        $query->where('user_id', $user->id);
    }
    $store = $query->first();

    if (!$store) {
        // Use your custom json response helper
        return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Store not found', $store);
    }

    if (isset($data['name'])) {
        $data['slug'] = Str::slug($data['name']);
    }

    $store->update($data);

    return $store;

}

public function deleteStore($id)
{
    $user = Auth::user();

    $query = Store::where('id', $id);

    if ($user->role && $user->role->name !== 'admin') {
        $query->where('user_id', $user->id);
    }

    $store = $query->first();

    if (!$store) {
        // Custom response (you can replace with abort(404, ...) if needed)
        return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Store not found');
    }else{



    $store->delete();

    return $this->jsonResponse(HTTP::HTTP_ACCEPTED, 'Store deleted successfully', $store);
}
}

public function createStore(array $data)
{
    // Automatically generate slug from name
    $data['slug'] = Str::slug($data['name']);

    return Store::create($data);
}

public function getAllPublicStores()
{
    return Store::where('status', 'active')->with('user')->latest()->get();
}

   
}
