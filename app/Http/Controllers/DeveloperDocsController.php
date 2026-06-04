<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DeveloperDocsController extends Controller
{
    /**
     * Show the mobile API documentation page.
     */
    public function index()
    {
        Gate::authorize('manage-settings');

        return Inertia::render('Developer/ApiDocs');
    }
}
