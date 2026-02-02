<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\Products;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LevelController extends Controller
{
     public function index()
    {
        $levels = Level::all();
        return response()->json($levels);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'levelName' => 'required|string|max:255',
    ]);

    $level = Level::create($validated);
    return response()->json($level, 201);
}

   public function update(Request $request, $levelId)
{
    $validated = $request->validate([
        'levelName' => 'required|string|exists:level,levelId',
    ]);

    $level = Level::where('levelId', $levelId)->first();
    if (!$level) {
        return response()->json(['message' => 'Level not found'], 404);
    }

    $level->update($validated);
    return response()->json($level, 200);
}

    public function destroy($levelId)
    {
        $level = Level::where('levelId', $levelId)->first();
        if (!$level) {
            return response()->json(['message' => 'Level not found'], 404);
        }

        $level->delete();
        return response()->json(['message' => 'Level deleted successfully'], 200);
    }


    public function currentLevel($level)
    {
        $level = Level::where('levelId', $level)->first();
        if (!$level) {
            return response()->json(['message' => 'Level not found'], 404);
        }

        return response()->json($level);
    }
    }
