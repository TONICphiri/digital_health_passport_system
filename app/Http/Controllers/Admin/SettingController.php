<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * System wide settings. Each setting carries its own label, group, input
 * type and help text in the database, so this page is built entirely from
 * the settings table.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'groups' => Setting::query()->orderBy('group')->orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request, SettingService $settings, AuditLogger $audit): RedirectResponse
    {
        $definitions = Setting::query()->get()->keyBy('key');

        $rules = $definitions->mapWithKeys(fn (Setting $setting) => [
            "settings.{$setting->key}" => match ($setting->input_type) {
                'number' => ['required', 'integer', 'min:0', 'max:1000'],
                'boolean' => ['nullable', 'in:0,1'],
                'list' => ['required', 'string', 'max:5000'],
                'email' => ['nullable', 'email', 'max:150'],
                default => ['required', 'string', 'max:255'],
            },
        ])->all();

        $attributes = $definitions->mapWithKeys(fn (Setting $setting) => [
            "settings.{$setting->key}" => strtolower($setting->label),
        ])->all();

        $validated = $request->validate($rules, [], $attributes)['settings'] ?? [];

        $values = $definitions->mapWithKeys(fn (Setting $setting) => [
            $setting->key => $setting->input_type === 'boolean'
                ? ($validated[$setting->key] ?? '0')
                : ($validated[$setting->key] ?? null),
        ])->all();

        $settings->update($values);
        $audit->record('settings.updated', 'Updated the system settings.');

        return back()->with('success', 'The settings have been saved.');
    }
}
