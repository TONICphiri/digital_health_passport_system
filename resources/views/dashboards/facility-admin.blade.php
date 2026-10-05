<x-layouts.app title="Dashboard">
    <x-page-header :title="auth()->user()->facility->name">
        <x-slot:actions>
            <a href="{{ route('facility.staff.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Register health worker</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active health workers" :value="number_format($stats['staff'])" icon="users" :href="route('facility.staff.index')" />
        <x-stat label="Passports opened today" :value="number_format($stats['openedToday'])" icon="qr" />
        <x-stat label="Encounters today" :value="number_format($stats['encountersToday'])" icon="stethoscope" />
        <x-stat label="Passports issued this month" :value="number_format($stats['issuedThisMonth'])" icon="heart" :href="route('facility.reports')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Health workers</h2>
                <a href="{{ route('facility.staff.index') }}" class="link text-sm">All health workers</a>
            </div>
            @forelse ($staff as $member)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <div><p class="font-medium">{{ $member->name }}</p><p class="text-[13px] text-muted">{{ $member->job_title }}</p></div>
                    <x-status :value="$member->status" />
                </div>
            @empty
                <x-empty title="No health workers yet" icon="users" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Recent activity</h2>
                <a href="{{ route('audit-log.index') }}" class="link text-sm">Activity log</a>
            </div>
            @include('partials.activity-list', ['activity' => $activity])
        </section>
    </div>
</x-layouts.app>
