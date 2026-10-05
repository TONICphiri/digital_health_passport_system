@if ($users->isEmpty())
    <x-empty title="No accounts found" icon="users" />
@else
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr><th>Name</th><th>Role</th>@if ($showFacility)<th>Facility</th>@endif<th>Last sign in</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><p class="font-medium">{{ $user->name }}</p><p class="text-[13px] text-muted">{{ $user->email }}</p></td>
                        <td><span class="badge-{{ $user->role()?->tone() ?? 'neutral' }}">{{ $user->roleLabel() }}</span>@if ($user->job_title)<p class="mt-1 text-[13px] text-muted">{{ $user->job_title }}</p>@endif</td>
                        @if ($showFacility)<td>{{ $user->facility?->name }}</td>@endif
                        <td class="text-muted">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td><x-status :value="$user->status" />@if ($user->must_change_password)<p class="mt-1 text-[12px] text-muted">Password not yet changed</p>@endif</td>
                        <td class="text-right"><a href="{{ route($editRoute, $user) }}" class="link text-sm">Manage</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endif
