<?php

namespace App\Http\Controllers;
use App\Services\StoreService;
use App\Http\Requests\StoreRequest;
use App\Traits\HasJsonResponse;
use App\Support\HttpConstants as HTTP;

use Illuminate\Http\Request;

class StoreController extends Controller
{
    use HasJsonResponse;
        protected $storeService;
    
        public function __construct(StoreService $storeService)
        {
            $this->storeService = $storeService;
        }
    
        public function index()
        {

            $stores = $this->storeService->getAllStores();
        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Store list fetched successfully', $stores);
        }
    
        public function store(StoreRequest $request)
        {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('public/store_logos', $filename); // Save to storage/app/public/store_logos
                $data['logo'] = 'storage/store_logos/' . $filename; // Publicly accessible path
            }
    
            $store = $this->storeService->createStore($data);
            return $this->jsonResponse(HTTP::HTTP_CREATED, 'Store created successfully', $store);
        }
    
        public function show($id)
        {
            $store = $this->storeService->getStoreById($id);
            return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Store fetched successfully', $store);
        }
    
        public function update(StoreRequest $request, $id)
        {
            $data = $request->validated();
           return $this->storeService->updateStore($id, $data);
            
        }
    
        public function destroy($id)
        {
            return $this->storeService->deleteStore($id);
        }

        public function publicIndex()
{
    $stores = $this->storeService->getAllPublicStores();
    return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Public store list fetched successfully', $stores);
}

}
