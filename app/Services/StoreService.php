<?php
namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\HasJsonResponse;
use App\Support\HttpConstants as HTTP;
use App\Enums\UserRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
class StoreService
{
    use HasJsonResponse;
    public function getAllStores(): LengthAwarePaginator|Collection
{
    $user = Auth::user();
    if ($user->role && $user->role->name === UserRole::ADMIN->value) {
        return Store::with('user')->paginate(20);
    }

    // Non-admins see only their own stores
    return Store::where('user_id', $user->id)->with('user')->get();
}

public function getStoreById($id): Model|JsonResponse|null
{
    $user = Auth::user();

    $query = Store::where('id', $id)->with('user');

    if ($user->role && $user->role->name !== UserRole::ADMIN->value) {
        $query->where('user_id', $user->id);
    }

    $store = $query->first();

    if (!$store) {
        return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Store not found', $store); 
    }

    return $store;
}

public function updateStore($id, array $data): Model|JsonResponse|null
{
    $user = Auth::user();

    $query = Store::where('id', $id);

    if ($user->role && $user->role->name !== UserRole::ADMIN->value) {
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

    if ($user->role && $user->role->name !== UserRole::ADMIN->value) {
        $query->where('user_id', $user->id);
    }

    $store = $query->first();

    if (!$store) {
        return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Store not found');
    }
}

public function createStore(array $data):store
{
    $data['slug'] = Str::slug($data['name']);

    return Store::create($data);
}

public function getAllPublicStores():collection
{
    return Store::where('status', 'active')->with('user')->latest()->get();
}

   
}
