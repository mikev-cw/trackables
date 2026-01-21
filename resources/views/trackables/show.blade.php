<x-layout>
    <x-slot name="pretitle">Trackable View</x-slot>
    <x-slot name="title">{{$trackable->name}}</x-slot>

    <h3>Schema</h3>
    @foreach($schema as $field)
        - {{$field->uid}} -> {{$field->name}} <br>
    @endforeach
    <hr>
    <h3>Records</h3>
    @foreach($records as $record)
        <strong>Record Date: {{$record->record_date}}</strong><br>
        @foreach($record->data as $field)
            {{$field->trackable_schema_uid}} : {{$field->value}}<br>
        @endforeach
        <hr>
    @endforeach

    {{ $records->links() }}

</x-layout>
