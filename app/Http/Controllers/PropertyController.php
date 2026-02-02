<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\AdmissionSettings;
use App\Models\Applications;
use App\Models\Programmes;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use PDF;

class PropertyController extends Controller
{
    public function index()
    {
        return Property::where('isAvailable', true)
            ->with('images','owner')
            ->latest()
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'propertyTitle' => 'required',
            'propertyDescription' => 'required',
            'propertyType' => 'required',
            'address' => 'required',
            'city' => 'nullable',
            'state' => 'nullable',
            'price' => 'required|numeric',
            'listingType' => 'required|in:rent,sale',
        ]);

        $property = auth()->user()->properties()->create($data);

        return response()->json($property, 201);
    }
}
