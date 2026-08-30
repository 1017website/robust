@props(['id' => 'indonesianCityOptions'])
{{-- Saran seluruh kota & kabupaten di Indonesia. Input tetap teks bebas. --}}
<datalist id="{{ $id }}">
    @foreach(\App\Support\IndonesianRegions::cities() as $city => $province)
        <option value="{{ $city }}" label="{{ $province }}"></option>
    @endforeach
</datalist>
