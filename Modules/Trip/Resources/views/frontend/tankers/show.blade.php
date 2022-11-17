@extends('trip::frontend.layouts.app')

@section('title') {{ __("Donatur") }} @endsection

@section('content')

<div class=" container z-2">
    <div class="row">
        <div class="col mb-5">
            <button class="btn btn-sm btn-warning my-2" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Kembali</button>
            <div class="card bg-white border-light shadow-soft no-gutters p-4">
                <div class="row">
                    <div class="col-3">
                        <img src="{{$inspection->photo ? asset($inspection->photo) : asset('img/default-avatar.jpg') }}" class="img-thumbnail img-fluid" alt="Inspection image">
                    </div>
                    <div class="col-9">
                        <h2 class="display-5 mt-2" style="font-size:45px"> {{$inspection->name}} </h2>
                        @php
                            $birthdate = Carbon\Carbon::createFromFormat('Y-m-d', $inspection->birth_date);
                            $age = $birthdate->diffInYears(Carbon\Carbon::now());
                        @endphp
                        <h3 class="display-6 mt-2"> {{$age}} Tahun</h3>
                        <h4 class="display-6 mt-2"> {{$inspection->major}} - {{$inspection->year_class}} </h4>
                        <table>
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold">Gender </td>
                                    <td>: {{$inspection->gender}}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Agama </td>
                                    <td>: {{$inspection->religion}}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">TB/BB </td>
                                    <td>: {{$inspection->height}} cm / {{$inspection->weight}} kg</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Keahlian </td>
                                    <td>: {{$inspection->skills}}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Sertifikasi </td>
                                    <td>: {{$inspection->certificate}}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="my-3">
                            @if($inspection->available)
                                @if($inspection->checkBookedBy(auth()->user()->corporation->id ?? 0))
                                    <button class="btn btn-lg btn-danger choose-inspection" data-id="{{$inspection->id}}" id="choose-inspection-{{$inspection->id}}">BATAL</button>
                                @else
                                    <button class="btn btn-lg btn-success choose-inspection" data-id="{{$inspection->id}}" id="choose-inspection-{{$inspection->id}}">PILIH</button>
                                @endif
                            @else
                                <div class="btn-lg btn-secondary-o disabled" id="">Currently Not Available</div>
                                @if($inspection->checkBookedBy(auth()->user()->corporation->id ?? 0))
                                    <button class="btn btn-lg btn-danger choose-inspection with-warning" data-id="{{$inspection->id}}" id="choose-inspection-{{$inspection->id}}">BATAL</button>
                                @else
                                    <!--  -->
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push ('after-styles')
@endpush

@push ('after-scripts')

@include("trip::frontend.inspections.dynamic-scripts")

@endpush