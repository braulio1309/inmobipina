<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyShareController extends Controller
{
    /**
     * Display the specified property for public sharing
     * This method does not require authentication
     */
    public function show($id)
    {
        $property = Property::with('images')->findOrFail($id);
        
        return view('properties.share', compact('property'));
    }
}
