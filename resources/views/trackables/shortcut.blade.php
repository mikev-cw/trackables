<x-layout>
    <x-slot name="pretitle">{{ $trackable->name }}</x-slot>
    <x-slot name="title">Apple Shortcuts</x-slot>
    <x-slot name="actions">
        <a class="btn btn-outline-primary" href="{{ route('trackables.show', $trackable) }}">Back to records</a>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <p>Add records to {{ $trackable->name }} from your iPhone, iPad, or Mac. The shortcut loads the latest fields and list choices every time you run it.</p>
            <ol>
                <li class="mb-3"><a class="btn btn-primary" href="{{ route('trackables.shortcut.download', $trackable) }}">Download Shortcuts file</a> and open it in Apple Shortcuts.</li>
                <li class="mb-3">During setup, enter an API token and the values below. You can also edit the first three Text actions after importing.</li>
                <li>Rename the shortcut to {{ $trackable->name }} so you can find it easily.</li>
            </ol>
            <label class="form-label" for="shortcut-url">API URL</label>
            <input class="form-control mb-3" id="shortcut-url" value="{{ url('/api/trackable') }}" readonly onclick="this.select()">
            <label class="form-label" for="shortcut-uid">Trackable UID</label>
            <input class="form-control mb-3" id="shortcut-uid" value="{{ $trackable->uid }}" readonly onclick="this.select()">
            <p class="text-secondary">Use an address reachable from your device. Optional lists include “Don’t fill”; leave other optional answers empty to skip them. Optional dates use YYYY-MM-DD and datetimes use YYYY-MM-DDTHH:mm:ss±HH:mm.</p>
            @if(session('shortcut_token'))
                <label class="form-label" for="shortcut-token">API token — copy it now; it is only shown once</label>
                <input class="form-control mb-3" id="shortcut-token" value="{{ session('shortcut_token') }}" readonly onclick="this.select()" autocomplete="off">
            @else
                <form method="POST" action="{{ route('trackables.shortcut.token', $trackable) }}">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit">Create API token</button>
                </form>
            @endif
            <p class="text-secondary mt-3 mb-0">You can reuse an existing API token. A new token allows access to your account’s API. Keep the configured shortcut private because it contains your token.</p>
        </div>
    </div>
</x-layout>
