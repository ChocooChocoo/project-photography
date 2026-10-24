{{-- Pick which owned studio a page works on. Shown only for owners with more than one studio.
     Params: $studios, $studio (selected), $route (GET route name), $label, $help. --}}
@if($studios->count() > 1)
    <form method="GET" action="{{ route($route) }}" class="row mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label" for="studioPicker">{{ $label }}</label>
            <select class="form-select" id="studioPicker" name="studio_id" aria-describedby="studioPickerHelp" onchange="this.form.submit()">
                @foreach($studios as $ownedStudio)
                    <option value="{{ $ownedStudio->id }}" @selected($studio?->id === $ownedStudio->id)>{{ $ownedStudio->studio_name }}</option>
                @endforeach
            </select>
            <div id="studioPickerHelp" class="form-text">{{ $help }}</div>
        </div>
    </form>
@endif
