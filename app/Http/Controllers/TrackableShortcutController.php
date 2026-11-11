<?php

namespace App\Http\Controllers;

use App\Models\Trackable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackableShortcutController extends Controller
{
    public function show(Trackable $trackable)
    {
        return response()->view('trackables.shortcut', compact('trackable'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function download(Trackable $trackable)
    {
        return response()->download(
            base_path('docs/shortcuts/Trackables - New Record.shortcut'),
            (Str::slug($trackable->name) ?: 'trackable').'.shortcut',
            ['Content-Type' => 'application/octet-stream', 'Cache-Control' => 'private, no-store']
        );
    }

    public function token(Request $request, Trackable $trackable)
    {
        $token = $request->user()->createToken('Apple Shortcuts: '.$trackable->uid)->plainTextToken;

        return redirect()->route('trackables.shortcut', $trackable)
            ->with('shortcut_token', $token);
    }
}
