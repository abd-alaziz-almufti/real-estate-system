<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::query()
            ->available()
            ->with([
                'property:id,name,address,description,location_id,company_id',
                'property.location',
                'primaryImage'
            ])
            ->paginate(12);

        return UnitResource::collection($units);
    }

    public function featured()
    {
        $units = Unit::with(['property:id,name,address,description,location_id,company_id', 'property.location', 'property.company:id,name,email'])
            ->with('primaryImage')
            ->available()
            ->featured()
            ->latest()
            ->limit(5)
            ->get();


        return UnitResource::collection($units);

    }

    public function show(Unit $unit)
    {

        $unit->load([
            'property:id,name,address,description,location_id,company_id',
            'property.location',
            'property.company:id,name,email',
            'images',
            'features',
        ]);


        return new UnitResource($unit);
    }
}