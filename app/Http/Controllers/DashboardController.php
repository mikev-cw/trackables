<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $listCollection = $this->getTrackables();
        $list = $listCollection->resource;
        return view('dashboard', compact('list'));
    }

    public function getTrackables() {
        // Implementation for fetching trackables
        $t = new TrackableController();
        return $t->list(request());
    }
}
