@extends('admin.layout')

@section('title', 'Pengaturan Website')
@section('page-title', 'Pengaturan Website')
@section('breadcrumb', 'Pengaturan Website')

@section('content')
    <div class="admin-page-intro">
        <div>
            <h2>Atur tampilan dan informasi publik</h2>
            <p>Perubahan akan langsung digunakan oleh halaman website.</p>
        </div>
    </div>

    <div class="admin-settings-layout">
        <nav class="admin-settings-nav" aria-label="Kategori pengaturan">
            @foreach ($sections as $sectionKey => $sectionLabel)
                <a href="{{ route('admin.settings.index', ['section' => $sectionKey]) }}" class="{{ $section === $sectionKey ? 'is-active' : '' }}">{{ $sectionLabel }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="admin-card admin-form admin-settings-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="{{ $section }}">

            <div class="admin-card-heading">
                <div><h2>{{ $sections[$section] }}</h2><p>Isi bagian yang ingin diubah, lalu simpan.</p></div>
            </div>

            <div class="admin-form-grid">
                @foreach ($fields as $key => $field)
                    @php
                        $fieldName = \Illuminate\Support\Str::after($key, $section.'.');
                        $inputName = 'settings['.$section.']['.str_replace('.', '][', $fieldName).']';
                        $settingPath = 'settings.'.$key;
                        $value = old('settings.'.$key, $values[$key] ?? null);
                    @endphp
                    <div class="admin-field {{ in_array($field['type'], ['textarea', 'image', 'video_source'], true) ? 'admin-field-full' : '' }}">
                        @if ($field['type'] === 'checkbox')
                            <input type="hidden" name="{{ $inputName }}" value="0">
                            <label class="admin-checkbox-label">
                                <input type="checkbox" name="{{ $inputName }}" value="1" @checked((bool) $value)>
                                <span>{{ $field['label'] }}</span>
                            </label>
                        @elseif ($field['type'] === 'video_source')
                            @php
                                $videoValue = is_string($value) ? $value : '';
                                $currentAsset = $videoAssets->first(fn (\App\Models\MediaAsset $asset): bool => $asset->publicPath() === $videoValue);
                                $youtubeId = \App\Support\VideoSource::youtubeId($videoValue);
                                $source = old($settingPath.'.source', $currentAsset ? 'media' : ($videoValue === '' ? 'none' : 'url'));
                                $videoUrl = old($settingPath.'.url', $youtubeId && ! str_starts_with($videoValue, 'http')
                                    ? 'https://youtu.be/'.$youtubeId
                                    : ($currentAsset ? '' : $videoValue));
                                $assetId = old($settingPath.'.asset_id', $currentAsset?->id);
                            @endphp
                            <label for="setting-{{ str_replace('.', '-', $key) }}-source">{{ $field['label'] }}</label>
                            <select id="setting-{{ str_replace('.', '-', $key) }}-source" name="{{ $inputName }}[source]">
                                <option value="none" @selected($source === 'none')>Tidak ditampilkan</option>
                                <option value="media" @selected($source === 'media')>Pilih dari Media Manager</option>
                                <option value="url" @selected($source === 'url')>Gunakan URL video</option>
                            </select>
                            <label for="setting-{{ str_replace('.', '-', $key) }}-url">URL YouTube atau tautan langsung MP4/WebM/OGG</label>
                            <input
                                id="setting-{{ str_replace('.', '-', $key) }}-url"
                                name="{{ $inputName }}[url]"
                                type="url"
                                value="{{ $videoUrl }}"
                                placeholder="https://youtu.be/... atau https://.../video.mp4"
                            >
                            <label for="setting-{{ str_replace('.', '-', $key) }}-asset">Video tersimpan</label>
                            <select id="setting-{{ str_replace('.', '-', $key) }}-asset" name="{{ $inputName }}[asset_id]">
                                <option value="">Pilih video dari Media Manager</option>
                                @foreach ($videoAssets as $asset)
                                    <option value="{{ $asset->id }}" @selected((string) $assetId === (string) $asset->id)>{{ $asset->filename }}</option>
                                @endforeach
                            </select>
                            <small>
                                <a href="{{ route('admin.media.index', ['type' => 'video']) }}">Unggah atau kelola video di Media Manager</a>.
                                Satu slot hanya memakai sumber yang dipilih.
                            </small>
                        @else
                            <label for="setting-{{ str_replace('.', '-', $key) }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'textarea')
                                <textarea id="setting-{{ str_replace('.', '-', $key) }}" name="{{ $inputName }}" rows="4">{{ $value }}</textarea>
                            @elseif ($field['type'] === 'image')
                                @php
                                    $category = in_array(\Illuminate\Support\Str::before($fieldName, '.'), ['hero', 'news', 'program', 'gallery', 'teacher'], true)
                                        ? \Illuminate\Support\Str::before($fieldName, '.')
                                        : 'general';
                                @endphp
                                @include('admin.media._upload-field', [
                                    'id' => 'setting-'.str_replace('.', '-', $key),
                                    'inputName' => 'uploads['.$section.']['.str_replace('.', '][', $fieldName).']',
                                    'sourceName' => 'source_uploads['.$section.']['.str_replace('.', '][', $fieldName).']',
                                    'assetInputName' => 'media_asset_ids['.$section.']['.str_replace('.', '][', $fieldName).']',
                                    'category' => $category,
                                    'existingPath' => $value,
                                    'label' => $field['label'],
                                    'errorKey' => 'uploads.'.$key,
                                    'embedded' => true,
                                ])
                                @if ($value)
                                    <label class="admin-checkbox-label admin-remove-image">
                                        <input type="checkbox" name="remove_images[{{ $section }}][{{ str_replace('.', '][', $fieldName) }}]" value="1">
                                        <span>Hapus gambar saat ini</span>
                                    </label>
                                @endif
                            @else
                                <input id="setting-{{ str_replace('.', '-', $key) }}" name="{{ $inputName }}" type="{{ in_array($field['type'], ['link', 'youtube'], true) ? 'text' : $field['type'] }}" value="{{ $value }}" @if ($field['type'] === 'link') inputmode="url" placeholder="/path atau https://..." @elseif ($field['type'] === 'youtube') placeholder="ID video YouTube 11 karakter" @endif>
                            @endif
                        @endif
                        @error('settings.'.$key)<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('settings.'.$key.'.source')<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('settings.'.$key.'.url')<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('settings.'.$key.'.asset_id')<small class="admin-field-error">{{ $message }}</small>@enderror
                        @error('uploads.'.$key)<small class="admin-field-error">{{ $message }}</small>@enderror
                    </div>
                @endforeach
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button admin-button-primary">Simpan pengaturan</button>
            </div>
        </form>
    </div>
@endsection
