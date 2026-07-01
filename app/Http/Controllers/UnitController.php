<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function UnitList(Request $request)
    {
        $query = Unit::query()
            ->where('is_active', true)
            ->orderByRaw("FIELD(type, 'weight', 'volume', 'count', 'package')")
            ->orderBy('name');

        if ($request->boolean('base_only')) {
            $query->whereIn('type', ['weight', 'volume', 'count']);
        }

        return $query->get();
    }
}
