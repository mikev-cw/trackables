<?php

namespace App\Http\Controllers;

use App\Models\Trackable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $list = Trackable::query()
            ->where('user_id', request()->user()->id)
            ->withMax('records', 'record_date')
            ->withCount('schema')
            ->orderBy('deleted')
            ->orderByDesc('updated_at')
            ->paginate(12);

        return view('dashboard', compact('list'));
    }
}
