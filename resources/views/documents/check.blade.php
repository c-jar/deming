@extends("layout")

@section("title")
Documents
@endsection

@section("content")
<?php
function bytesToHuman($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $bytes > 1024; $i++) { $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
<table class="table">
    <thead>
    <tr>
        <th>#</th>
        <th>Contrôle</th>
        <th>Nom</th>
        <th>Taille</th>
        <th>Hash</th>
    </tr>
    </thead>

    @foreach ($documents as $doc)
    <tr>
        <td>
            {{ $doc->id }}
        </td>
        <td>
            <a href="/control/show/{{ $doc->control_id }}">{{ $doc->control->clause }}</a>
        </td>
        <td>
            <a href="/doc/show/{{ $doc->id }}">{{ substr($doc->filename,0,32) }}</a>
        </td>
        <td>
            {{ bytesToHuman($doc->size) }}
        </td>
        <td>
            {{ $doc->hash }}
            <br>
        </td>
        <td>    
            <b>        
            @if (file_exists(storage_path('docs/').$doc->id))            
                @if ($doc->hash == hash_file("sha256", storage_path('docs/').$doc->id))
                    <font color="green">OK</font>
                @else
                    <font color="red">HASH FAILS</font>
                @endif
            @else
                    <font color="red">MISSING</font>
            @endif
            </b>
        </td>
    </tr>
    @endforeach
</table>

@endsection
