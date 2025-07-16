<?php

namespace App\Http\Controllers;

use App\Models\SubmenuOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class SubmenuOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $submenuOptions = SubmenuOption::all();
        return view('submenu_options.index', compact('submenuOptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('submenu_options.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:255',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'text.required' => 'The text field is required.',
            'text.max' => 'The text may not be greater than 255 characters.',
            'icon.required' => 'An icon image is required.',
            'icon.image' => 'The file must be an image.',
            'icon.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg, or webp.',
            'icon.max' => 'The image may not be greater than 2MB in size.'
        ]);

        try {
            $iconPath = $request->file('icon')->store('submenu_options', 'public');

            SubmenuOption::create([
                'text' => $validated['text'],
                'icon' => $iconPath,
            ]);

            return redirect()->route('submenu-options.index')
                ->with('success', 'Submenu option created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error creating submenu option: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SubmenuOption $submenuOption) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubmenuOption $submenuOption)
    {
        return view('submenu_options.edit', ['option' => $submenuOption]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubmenuOption $submenuOption)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'text.required' => 'The text field is required.',
            'text.max' => 'The text may not be greater than 255 characters.',
            'icon.image' => 'The file must be an image.',
            'icon.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg, or webp.',
            'icon.max' => 'The image may not be greater than 2MB in size.'
        ]);

        try {
            $data = ['text' => $validated['text']];

            if ($request->hasFile('icon')) {
                // Delete old icon if exists
                if ($submenuOption->icon) {
                    Storage::disk('public')->delete($submenuOption->icon);
                }
                // Store new icon
                $data['icon'] = $request->file('icon')->store('submenu_options', 'public');
            }

            $submenuOption->update($data);

            return redirect()->route('submenu-options.index')
                ->with('success', 'Submenu option updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error updating submenu option: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        if (!Gate::allows('delete webpages')) {
            abort(403);
        }

        $submenuOption = SubmenuOption::findOrFail($id);
        $submenuOption->delete();

        return response()->json([
            'success' => true,
            'message' => 'Submenu option deleted successfully.'
        ]);
    }
}
